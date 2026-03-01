<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models\Content;

use Alexeyplodenko\Sitecode\Models\Content\AbstractContent;
use Alexeyplodenko\Sitecode\Services\ContentAccessor;

class ContentCollection implements \Iterator
{
    protected int $pointer = 0;
    protected array $plainContentItems = [];

    /**
     * @param (AbstractContent|ContentCollection)[] $items
     * @param array $parentTitle
     */
    public function __construct(protected array $items = [], protected array $parentTitle)
    {
        $this->makeDirectAccessItems();
    }

    public function current()
    {
        return $this->items[$this->pointer];
    }

    public function key()
    {
        return $this->pointer;
    }

    public function next()
    {
        $this->pointer++;
    }

    public function rewind()
    {
        $this->pointer = 0;
    }

    public function valid()
    {
        return isset($this->items[$this->pointer]);
    }

    public function get(string|int|array $title): AbstractContent|ContentCollection|null
    {
        if (is_int($title)) {
            return $this->items[$title] ?? null;
        }

        $titleNorm = ContentAccessor::normalizeTitle($title);

        if ($title === $titleNorm) {
            // maybe the user tries to access the item in a tree directly
            $content = $this->items[$title] ?? null;
            if ($content) {
                return $content;
            }
        }

        return $this->plainContentItems[$titleNorm] ?? null;
    }

    public function has(string|int|array $title): bool
    {
        return (bool)$this->get($title);
    }

    public function hasContent(string|int|array $title): bool
    {
        return $this->has($title);
    }

    public function getContent(string|int|array $title): AbstractContent|ContentCollection|null
    {
        return $this->get($title);
    }

    public function getContentPlain()
    {
        return $this->plainContentItems;
    }

    protected function makeDirectAccessItems()
    {
        foreach ($this->items as $itemTitle => &$item) {
            if ($item instanceof ContentCollection) {
                // we want to allow to access the internal content directly using it's key,
                // rather than traversing the tree structure.

                $this->plainContentItems += $item->getContentPlain();

            } else {
                $itemTitleNorm = ContentAccessor::normalizeTitle(array_merge($this->parentTitle, [$itemTitle]));
                $this->plainContentItems[$itemTitleNorm] = $item;
            }
        }
        unset($item);
    }
}
