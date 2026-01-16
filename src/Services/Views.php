<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class Views
{
    public function __construct(protected ?string $path = null)
    {
        if (!isset($this->path)) {
            $this->path = resource_path('views');
        }
        $this->path = $this->normalizePath($this->path);
    }

    public function all(): ViewsCollection
    {
        $files = [];

        $directory = new RecursiveDirectoryIterator($this->path);
        $iterator = new RecursiveIteratorIterator($directory);
        $regExIterator = new RegexIterator($iterator, '/\.blade\.php$/');
        $sortedIterator = new SplFileSortedIterator($regExIterator);
        foreach ($sortedIterator as $file) {
            $files[] = $file;
        }

        $collection = new ViewsCollection($files);
        $collection->setBasePath($this->path);
        return $collection;
    }

    protected function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
