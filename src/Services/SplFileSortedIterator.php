<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use Iterator;
use SplFileInfo;
use SplHeap;

class SplFileSortedIterator extends SplHeap
{
    public function __construct(Iterator $iterator)
    {
        foreach ($iterator as $item) {
            $this->insert($item);
        }
    }

    /**
     * @param SplFileInfo $value1
     * @param SplFileInfo $value2
     * @return int
     */
    public function compare($value1, $value2): int
    {
        return strcmp($value2->getPathname(), $value1->getPathname());
    }
}
