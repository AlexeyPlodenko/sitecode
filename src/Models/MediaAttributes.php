<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models;

readonly class MediaAttributes
{
    /**
     * @param int $width
     * @param int $height
     * @param int $type A PHP constant IMAGETYPE_*
     * @param array $raw
     */
    public function __construct(public int $width, public int $height, public int $type, public array $raw = [])
    {
    }
}
