<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use Alexeyplodenko\Sitecode\Enums\ContentEditor;
use Alexeyplodenko\Sitecode\Models\Page;
use Alexeyplodenko\Sitecode\Models\PageField;
use Illuminate\Support\Facades\DB;
use Throwable;

class PageCloner
{
    /**
     * Clone a page with all properties and content except images/files.
     *
     * @throws Throwable
     */
    public function cloneWithoutImages(Page $source): Page
    {
        return DB::transaction(function () use ($source) {
            // Collect non-file field names
            $nonFileFields = [];
            foreach ($source->getPageFields()->getFieldsFlat() as $field) {
                if ($field instanceof PageField && $field->getEditor() !== ContentEditor::File) {
                    $nonFileFields[] = $field->getFieldName();
                }
            }

            // Prepare content without file fields
            $newContent = [];
            $sourceContent = (array) ($source->content ?? []);
            foreach ($nonFileFields as $name) {
                if (array_key_exists($name, $sourceContent)) {
                    $newContent[$name] = $sourceContent[$name];
                }
            }

            // Prepare new URL and title
            $copy = new Page();
            $copy->url = $this->makeUniqueUrl($source->url);
            $copy->title = $this->makeUniqueTitle($source->title);
            $copy->view = $source->view;
            $copy->cache = $source->cache;
            $copy->content = $newContent;
            $copy->save();

            return $copy;
        });
    }

    /**
     * Generate unique URL by appending "-copy" / " (copy)" with increment if needed.
     */
    protected function makeUniqueUrl(string $url): string
    {
        return $this->appendSuffixUnique($url, '-copy');
    }

    /**
     * Generate unique Title by appending "-copy" / " (copy)" with increment if needed.
     */
    protected function makeUniqueTitle(string $title): string
    {
        return  $this->appendSuffixUniqueTitle($title, ' (copy)');
    }

    protected function appendSuffixUnique(string $base, string $suffix): string
    {
        $try = $base . $suffix;
        $i = 2;
        while (Page::query()->where('url', $try)->exists()) {
            $try = $base . $suffix . '-' . $i;
            $i++;
        }
        return $try;
    }

    protected function appendSuffixUniqueTitle(string $base, string $suffix): string
    {
        $try = $base . $suffix;
        $i = 2;
        while (Page::query()->where('title', $try)->exists()) {
            $try = $base . $suffix . ' ' . $i;
            $i++;
        }
        return $try;
    }
}
