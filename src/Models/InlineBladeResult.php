<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models;

readonly class InlineBladeResult
{
    public function __construct(public string $bladeContent,
                                public array $embeddedViews)
    {
    }
}
