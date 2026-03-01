<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models;

use Alexeyplodenko\Sitecode\Enums\ContentEditor;
use Alexeyplodenko\Sitecode\Exceptions\Models\Page\CacheDisabled;
use Alexeyplodenko\Sitecode\Models\Content\AbstractContent;
use Alexeyplodenko\Sitecode\Models\Content\BooleanContent;
use Alexeyplodenko\Sitecode\Models\Content\FileContent;
use Alexeyplodenko\Sitecode\Models\Content\HtmlAbstractContent;
use Alexeyplodenko\Sitecode\Models\Content\TextContent;
use Alexeyplodenko\Sitecode\Services\BladeView;
use Alexeyplodenko\Sitecode\Services\ContentAccessor;
use Alexeyplodenko\Sitecode\Services\PagesCache;
use Alexeyplodenko\Sitecode\Models\Content\ContentCollection;
use Eloquent;
use Henrik9999\StringSimilarity\StringSimilarity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Route;
use RuntimeException;
use Alexeyplodenko\Sitecode\Services\SharedContentAccessor;

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
    protected Collection $sharedContent;
    protected array $sharedContentCache = [];
    protected ContentAccessor $contentAccessor;

    /** @var string[] */
    protected array $fieldsLookup;

    public function hasField(string $fieldName): bool
    {
        if (!isset($this->fieldsLookup)) {
            $this->fieldsLookup = array_flip($this->fields);
        }

        return isset($this->fieldsLookup[strtolower($fieldName)]);
    }

    public function getContentAccessor()
    {
        if (!isset($this->contentAccessor)) {
            $fields = $this->getPageFields();
            $this->contentAccessor = new ContentAccessor($fields, $this->content);
        }
        return $this->contentAccessor;
    }

    /**
     * @param (string|array)[] $titles
     * @return bool
     */
    public function hasAnyContent(array $titles): bool
    {
        return $this->getContentAccessor()->hasAny($title);
    }

    public function hasContent(string|array $title): bool
    {
        return $this->getContentAccessor()->has($title);
    }

    /**
     * @return AbstractContent|AbstractContent[]
     */
    public function getContent(string|array $title): AbstractContent|ContentCollection
    {
        return $this->getContentAccessor()->get($title);
    }

    public function hasSharedContent(string|array $title): bool
    {
        return (bool)$this->getSharedContent($title)?->content;
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
}
