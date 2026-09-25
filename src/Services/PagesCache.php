<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use Alexeyplodenko\Sitecode\Models\Page;
use Illuminate\Http\Request;

class PagesCache
{
    protected string $cacheDir = 'sitecode_static_cache';

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    public function getCachePath(): string
    {
        return public_path($this->cacheDir);
    }

    public function getFilePathFromPageUrl(string $url): ?string
    {
        $requestPath = trim($url, '/');
        if ($requestPath) {
            $requestPath .= '/';
        }
        $cachePath = $this->getCachePath();
        $filePath = "$cachePath/{$requestPath}index.html";

        return $this->isPathWithinBasePath($filePath) ? $filePath : null;
    }

    public function getFilePathFromPage(Page $page): ?string
    {
        return $this->getFilePathFromPageUrl($page->url);
    }

    public function getFilePathFromRequest(Request $request): ?string
    {
        $requestPath = $request->path();
        if (str_contains($requestPath, '..')) {
            return null;
        }

        // a few safety checks, since file vulnerabilities are really nasty
        $requestPath = trim($requestPath, '/');
        if ($requestPath) {
            $requestPath .= '/';
        }
        $cachePath = $this->getCachePath();
        $filePath = "$cachePath/{$requestPath}index.html";

        return $this->isPathWithinBasePath($filePath) ? $filePath : null;
    }

    /**
     * @var array<string, string[]> In-memory cache of shared field names by view.
     */
    protected array $viewSharedFieldsCache = [];

    public function invalidatePagesUsingSharedContent(array $sharedFieldNames, ?int $exceptPageId = null): int
    {
        if (empty($sharedFieldNames)) {
            return 0;
        }

        $query = Page::query()->where('cache', true);
        if ($exceptPageId) {
            $query->where('id', '!=', $exceptPageId);
        }

        $pages = $query->get(['id', 'url', 'view', 'cache']);
        if ($pages->isEmpty()) {
            return 0;
        }

        $pagesByView = $pages->groupBy('view');
        $invalidatedCount = 0;

        foreach ($pagesByView as $view => $viewPages) {
            if (!$view) {
                continue;
            }

            $viewSharedFields = $this->getSharedFieldNamesForView($view);
            if (array_intersect($sharedFieldNames, $viewSharedFields)) {
                foreach ($viewPages as $page) {
                    if ($page->invalidateCache()) {
                        $invalidatedCount++;
                    }
                }
            }
        }

        return $invalidatedCount;
    }

    public function getSharedFieldNamesForView(string $view): array
    {
        if (isset($this->viewSharedFieldsCache[$view])) {
            return $this->viewSharedFieldsCache[$view];
        }

        $viewDotPath = viewFromPath($view);
        $viewsPath = resource_path('views');

        $bladeView = BladeView::fromView($viewDotPath);
        $bladeView->setBasePath($viewsPath);

        if (!$bladeView->isFileExist()) {
            return $this->viewSharedFieldsCache[$view] = [];
        }

        $sharedFields = $bladeView->getPageFields()->getSharedFieldsFlat();

        $names = [];
        foreach ($sharedFields as $field) {
            $names[] = $field->getFieldName();
            $names[] = $field->getFullTitle();
        }

        return $this->viewSharedFieldsCache[$view] = array_values(array_unique(array_filter($names)));
    }

    protected function isPathWithinBasePath(string $path): bool
    {
        $basePath = base_path();
        return str_starts_with($path, $basePath);
    }
}
