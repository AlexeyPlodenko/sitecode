<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Enums;

use Alexeyplodenko\Sitecode\Enums\Traits\HasValues;

Enum PageState: int
{
    use HasValues;

    case Disabled = 0;
    case Enabled = 1;
    case Draft = 2;
}
