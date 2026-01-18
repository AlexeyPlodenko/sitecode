<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models;

use Alexeyplodenko\Sitecode\Enums\ContentEditor;
use Alexeyplodenko\Sitecode\Exceptions\Models\Page\CacheDisabled;
use Alexeyplodenko\Sitecode\Models\Content\AbstractContent;
use Alexeyplodenko\Sitecode\Models\Content\FileContent;
use Alexeyplodenko\Sitecode\Models\Content\HtmlAbstractContent;
use Alexeyplodenko\Sitecode\Models\Content\TextContent;
use Alexeyplodenko\Sitecode\Services\BladeView;
use Alexeyplodenko\Sitecode\Services\PagesCache;
use Eloquent;
use Henrik9999\StringSimilarity\StringSimilarity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Route;
use RuntimeException;

/**
 * @property int $id
 * @property string $url
 * @property string $title
 * @property string $view
 * @property bool $cache
 * @property ?string $content
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 *
 * @mixin Eloquent
 */
class Page extends Model
{
    const ON_NO_CONTENT_THROW = 1;
    const ON_NO_CONTENT_IGNORE = 2;
    const ON_NO_CONTENT_OUTPUT_PLACEHOLDER = 3;

    /** @var string[] */
    protected array $fields = ['id', 'url', 'title', 'view', 'content', 'created_at', 'updated_at'];

    /** var string[] */
    protected $fillable = [
        'url',
        'title',
        'view',
        'content',
        'cache'
    ];
    protected string $viewDotPath;
    protected PageFields $pageFields;
    protected string $fullUrl;

    /** @var array<string, string> */
    protected $casts = [
        'content' => 'array',
        'cache' => 'bool',
    ];
    protected string $noContentPlaceholder = 'CONTENT IS NOT SET';
    protected int $noContentAction = self::ON_NO_CONTENT_IGNORE;
    protected Collection $sharedContent;
    protected array $sharedContentCache = [];

    /** @var string[] */
    protected array $fieldsLookup;

    public function hasField(string $fieldName): bool
    {
        if (!isset($this->fieldsLookup)) {
            $this->fieldsLookup = array_flip($this->fields);
        }

        return isset($this->fieldsLookup[strtolower($fieldName)]);
    }

    public function setNoContentAction(int $action): static
    {
        $this->noContentAction = $action;

        return $this;
    }

    public function setNoContentPlaceholder(string $placeholder): static
    {
        $this->noContentPlaceholder = $placeholder;

        return $this;
    }

    /**
     * @param (string|array)[] $titles
     * @return bool
     */
    public function hasAnyContent(array $titles): bool
    {
        foreach ($titles as $title) {
            $titleNormalized = $this->normalizeTitle($title);

            if ((isset($this->content[$titleNormalized]) && $this->content[$titleNormalized]) || $this->hasSharedContent($titleNormalized)) {
                return true;
            }
        }

        return false;
    }

    public function hasContent(string|array $title): bool
    {
        $titleNormalized = $this->normalizeTitle($title);

        if (!$this->content || !isset($this->content[$titleNormalized])) {
            return $this->hasSharedContent($titleNormalized);
        }

        return (bool)$this->content[$titleNormalized];
    }

    public function getContent(string|array $title): AbstractContent
    {
        $titleNormalized = $this->normalizeTitle($title);

        if (!$this->content || !isset($this->content[$titleNormalized])) {
            $sharedContent = $this->getSharedContent($titleNormalized);
            if (isset($sharedContent)) {
                return $this->makeContentFromTitle($titleNormalized, $sharedContent->content ?? '');
            }

            return $this->handleNoContent($titleNormalized);
        }

        return $this->makeContentFromTitle($titleNormalized, $this->content[$titleNormalized]);
    }

    protected function makeContentFromTitle(string $title, string $content): AbstractContent
    {
        $field = $this->getPageFields()->getFieldByFullTitle($title);
        if (!$field) {
            throw new RuntimeException("Requested field \"$title\" not found in admin pages.");
        }

        return $this->makeContentFromEditor($field->getEditor(), $content);
    }


    protected function makeContentFromEditor(ContentEditor $editor, string $content): AbstractContent
    {
        // @TODO strategy pattern
        switch ($editor) {
            default:
                return TextContent::fromContent($content);

            case ContentEditor::File:
                return FileContent::fromContent($content);

            case ContentEditor::WYSIWYG:
                return HtmlAbstractContent::fromContent($content);
        }
    }

    public function hasSharedContent(string|array $title): bool
    {
        return (bool)$this->getSharedContent($title)?->content;
    }

    protected function getSharedContent(string|array $title): ?SharedContent
    {
        $titleNormalized = $this->normalizeTitle($title);

        if (isset($this->sharedContent)) {
            return $this->sharedContent->get($titleNormalized);
        }

        if (!isset($this->sharedContentCache[$titleNormalized])) {
            $this->sharedContentCache[$titleNormalized] = $this->getShareContentQuery()
                ->where('title', $titleNormalized)
                ->first();
        }

        return $this->sharedContentCache[$titleNormalized];
    }

    // @TODO make method cacheable using "php artisan optimize"
    public function getPageFields(): PageFields
    {
        if (!isset($this->pageFields)) {
            $viewsPath = resource_path('views');

            $bladeView = BladeView::fromView($this->geViewDotPath());
            $bladeView->setBasePath($viewsPath);
            $this->pageFields = $bladeView->getPageFields($this);
        }

        return $this->pageFields;
    }

    public function loadSharedContent(): void
    {
        if (!isset($this->sharedContent)) {
            $fullTitles = [];
            foreach ($this->getPageFields()->getSharedFieldsFlat() as $field) {
                $fullTitles[] = $field->getFullTitle();
            }

            $this->sharedContent = $this->getShareContentQuery()
                ->whereIn('title', $fullTitles)
                ->get()
                ->keyBy('title');
        }
    }

    public function invalidateCache(?string $filePath = null): bool
    {
        if (!$filePath) {
            $filePath = app(PagesCache::class)->getFilePathFromPage($this);
        }

        return @unlink($filePath);
    }

    public function isCached(?string $filePath = null): bool
    {
        if (!$filePath) {
            $filePath = app(PagesCache::class)->getFilePathFromPage($this);
        }

        return is_file($filePath);
    }

    /**
     * @throws CacheDisabled
     */
    public function cache(): true
    {
        if (!$this->cache) {
            throw new CacheDisabled();
        }

        // capture the original request to restore it later
        $originalRequest = request();

        $appHost = appHost();
        $subRequest = Request::create($this->url, server: [
            'HTTP_HOST' => $appHost,
            'SERVER_NAME' => $appHost,
        ]);
        app()->instance('request', $subRequest);
        $resp = Route::dispatch($subRequest);

        // handle failed cache request
        if (!$resp->isSuccessful()) {
            $errorCode = $resp->getStatusCode();
            $content = $resp->getContent();
            echo $content;
            abort($errorCode);
        }

        // restore the original request so Filament can finish rendering
        app()->instance('request', $originalRequest);

        return true;
    }

    public function makeFullUrl(): string
    {
        if (!isset($this->fullUrl)) {
            if (!str_starts_with($this->url, '//') && !str_contains($this->url, '://')) {
                // there is no scheme and host (domain) specified. Let's add those, so URL will be correct

                $this->fullUrl = config('app.url') . $this->url;
            } else {
                $this->fullUrl = $this->url;
            }
        }

        return $this->fullUrl;
    }

    protected function getShareContentQuery(): Builder
    {
        return SharedContent::query()
            ->select(['title', 'content']);
    }

    protected function getSimilarTitles(string $title, float $rating = 0.75): array
    {
        $res = [];

        $fullTitles = $this->getPageFields()->getFullTitlesFlat();

        $stringSimilarity = new StringSimilarity();
        $matches = $stringSimilarity->findBestMatch($title, $fullTitles)['ratings'];
        foreach ($matches as $match) {
            if ($match['rating'] < $rating) {
                break;
            }

            $res[] = $match['target'];
        }

        return $res;
    }

    protected function geViewDotPath(): string
    {
        if (!isset($this->viewDotPath)) {
            $this->viewDotPath = viewFromPath($this->view);
        }

        return $this->viewDotPath;
    }

    protected function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    protected function normalizeTitle(string|array $title): string
    {
        if (is_string($title)) {
            return $title;
        }

        return implode(': ', $title);
    }

    protected function handleNoContent(string $title): AbstractContent
    {
        switch ($this->noContentAction) {
            case static::ON_NO_CONTENT_THROW:
                $this->throwNoContent($title);

            case static::ON_NO_CONTENT_IGNORE:
                return $this->makeContentFromEditor(ContentEditor::TextInput, '');

            case static::ON_NO_CONTENT_OUTPUT_PLACEHOLDER:
                return $this->makeContentFromEditor(ContentEditor::TextInput, $this->noContentPlaceholder);

            default:
                throw new RuntimeException('Unknown no content action.');
        }
    }

    protected function throwNoContent(string $title, int $maxSimilarTitles = 10): void
    {
        $msg = "Could not find \"$title\".";

        $similarTitles = $this->getSimilarTitles($title);
        if ($similarTitles) {
            if ($maxSimilarTitles > 0) {
                $similarTitles = array_slice($similarTitles, 0, $maxSimilarTitles);
            }
            $msg .= ' Maybe you have meant: "' . implode('", "', $similarTitles) . '".';
        }

        throw new RuntimeException($msg);
    }
}
