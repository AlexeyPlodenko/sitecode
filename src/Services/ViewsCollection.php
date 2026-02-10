<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use Illuminate\Support\Collection;
use SplFileInfo;

class ViewsCollection extends Collection
{
    protected string $basePath;

    public function __construct($items = [], protected ?array $names = null)
    {
        parent::__construct($items);
    }

    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\\/');

        return $this;
    }

    public function forUserSelect(): static
    {
        assert(isset($this->basePath), 'Set the base path using ViewsCollection->setBasePath($path); method');

        $res = new static();
        $res->setBasePath($this->basePath);

        $basePathLen = mb_strlen($this->basePath) + 1;
        return $this->mapWithKeys(function(SplFileInfo $file, $i) use ($basePathLen) {
            $path = mb_substr($file->getPathname(), $basePathLen);

            return [$path => $this->names ? $this->names[$i] : $path];
        });
    }
}
