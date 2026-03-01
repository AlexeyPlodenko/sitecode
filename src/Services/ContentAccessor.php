<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use Alexeyplodenko\Sitecode\Enums\ContentEditor;
use Alexeyplodenko\Sitecode\Models\Content\AbstractContent;
use Alexeyplodenko\Sitecode\Models\Content\BooleanContent;
use Alexeyplodenko\Sitecode\Models\Content\ContentCollection;
use Alexeyplodenko\Sitecode\Models\Content\FileContent;
use Alexeyplodenko\Sitecode\Models\Content\HtmlAbstractContent;
use Alexeyplodenko\Sitecode\Models\Content\TextContent;
use Alexeyplodenko\Sitecode\Models\PageFields;
use Alexeyplodenko\Sitecode\Models\SharedContent;
use Alexeyplodenko\Sitecode\Services\SharedContentAccessor;

class ContentAccessor
{
    const ON_NO_CONTENT_THROW = 1;
    const ON_NO_CONTENT_IGNORE = 2;
    const ON_NO_CONTENT_OUTPUT_PLACEHOLDER = 3;

    protected SharedContentAccessor $sharedContentAccessor;
    protected string $noContentPlaceholder = 'CONTENT IS NOT SET';
    protected int $noContentAction = self::ON_NO_CONTENT_IGNORE;
    protected static string $titleSeparator = ': ';
    protected static int $titleSeparatorLen = 2;

    public function __construct(protected readonly PageFields $pageFields,
                                protected readonly array      $content)
    {
        $this->sharedContentAccessor = app(SharedContentAccessor::class);
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
    public function hasAny(array $titles): bool
    {
        foreach ($titles as $title) {
            $titleNormalized = static::normalizeTitle($title);

            if ((isset($this->content[$titleNormalized]) && $this->content[$titleNormalized]) || $this->sharedContentAccessor->has($titleNormalized)) {
                return true;
            }
        }

        return false;
    }

    public function has(string|array $title): bool
    {
        $titleNormalized = static::normalizeTitle($title);

        if (!$this->content || !isset($this->content[$titleNormalized])) {
            return $this->sharedContentAccessor->has($titleNormalized);
        }

        return (bool)$this->content[$titleNormalized];
    }

    /**
     * @return AbstractContent|AbstractContent[]
     */
    public function get(string|array $title): AbstractContent|ContentCollection
    {
        $titleNormalized = static::normalizeTitle($title);

        if (!$this->content || !isset($this->content[$titleNormalized])) {
            $sharedContent = $this->sharedContentAccessor->get($titleNormalized);
            if (isset($sharedContent)) {
                if (is_array($sharedContent)) {
                    $titleDenormalized = static::denormalizeTitle($title);
                    return $this->makeContentFromArray($titleDenormalized, $sharedContent);
                }

                return $this->makeContentFromTitle($titleNormalized, $sharedContent);
            }

            return $this->handleNoContent($titleNormalized);
        }

        $content = $this->content[$titleNormalized];
        if (is_array($content)) {
            $titleDenormalized = static::denormalizeTitle($title);
            return $this->makeContentFromArray($titleDenormalized, $content);
        }
        return $this->makeContentFromTitle($titleNormalized, $content);
    }

    public static function denormalizeTitle(string|array $title, ?string $titleSep = null): array
    {
        if (is_array($title)) {
            return $title;
        }

        return explode($titleSep ?? static::$titleSeparator, $title);
    }

    public static function normalizeTitle(string|array $title, ?string $titleSep = null): string
    {
        if (is_string($title)) {
            return $title;
        }

        return implode($titleSep ?? static::$titleSeparator, $title);
    }

    /**
     * @return ContentCollection
     */
    protected function makeContentFromArray(array $title, array $contentArray): ContentCollection
    {
        $list = [];
        foreach ($contentArray as $key => $value) {
            if (is_array($value)) {
                $list[] = $this->makeContentFromArray(array_merge($title, [$key]), $value);

            } elseif (isset($value)) {
                $lastSepPos = mb_strrpos($key, static::$titleSeparator);
                $lastKeyItem = $lastSepPos === false ? $key : mb_substr($key, $lastSepPos + static::$titleSeparatorLen);

                $list[$lastKeyItem] = $this->makeContentFromTitle($key, $value);
            }
        }

        return new ContentCollection($list, $title);
    }

    protected function makeContentFromTitle(string $title, string|bool $content): AbstractContent
    {
        $field = $this->pageFields->getFieldByFullTitle($title);
        if (!$field) {
            throw new RuntimeException("Requested field \"$title\" not found in admin pages.");
        }

        return $this->makeContentFromEditor($field->getEditor(), $content);
    }

    protected function makeContentFromEditor(ContentEditor $editor, string|bool $content): AbstractContent
    {
        // @TODO strategy pattern?
        switch ($editor) {
            default:
                return TextContent::fromContent($content);

            case ContentEditor::File:
                return FileContent::fromContent($content);

            case ContentEditor::WYSIWYG:
                return HtmlAbstractContent::fromContent($content);

            case ContentEditor::Checkbox:
                return BooleanContent::fromContent($content);
        }
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
