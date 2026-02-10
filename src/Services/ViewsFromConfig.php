<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use RuntimeException;

class ViewsFromConfig
{
    protected function __construct(protected array $views, protected ?string $path = null)
    {
        if (!isset($this->path)) {
            $this->path = resource_path('views');
        }
        $this->path = $this->normalizePath($this->path);
    }

    public static function make(?string $path = null): ?ViewsFromConfig
    {
        $views = config('sitecode.views');
        if (!isset($views)) {
            return null;
        }

        if (!is_array($views)) {
            throw new RuntimeException('Views key defined in /config/sitecode.php should be an array: '
                . '["view-name.blade.php" => "View name"]');
        }

        assert(
            !array_filter(
                $views,
                fn ($name, $view) => !is_string($view) || !is_string($name) || !$name || !str_ends_with($view, '.blade.php'),
                ARRAY_FILTER_USE_BOTH
            ),
            'Views key defined in /config/sitecode.php should be an array: '
            . '["view-name.blade.php" => "View name"]'
        );

        return new ViewsFromConfig($views, $path);
    }

    public function all(): ViewsCollection
    {
        $names = array_values($this->views);

        $files = [];
        $views = array_keys($this->views);
        foreach ($views as $view) {
            $files[] = new \SplFileInfo("$this->path/$view");
        }

        $collection = new ViewsCollection($files, $names);
        $collection->setBasePath($this->path);
        return $collection;
    }

    public function withAdminPagesOnly(): ViewsCollection
    {
        throw new RuntimeException('Not implemented.');
//        $files = [];
//
//        foreach ($this->views as $view => $name) {
//            $viewPath = "$this->path/$view";
//            $adminPath = mb_substr($view, 0, -9) . 'admin.php';
//            if (is_file($adminPath)) {
//                $files[] = $view;
//            }
//        }
//
//        $collection = new ViewsCollection($files);
//        $collection->setBasePath($this->path);
//        return $collection;
    }

    protected function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
