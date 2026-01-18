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

    protected function isPathWithinBasePath(string $path): bool
    {
        $basePath = base_path();
        return str_starts_with($path, $basePath);
    }
}
