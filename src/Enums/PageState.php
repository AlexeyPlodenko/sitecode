<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Enums;

use Alexeyplodenko\Sitecode\Enums\Traits\HasValues;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PageState: int implements HasLabel, HasColor
{
    use HasValues;

    case Disabled = 0;
    case Enabled = 1;
    case Draft = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Disabled => 'Disabled',
            self::Enabled => 'Enabled',
            self::Draft => 'Draft',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Disabled => 'danger',
            self::Enabled => 'success',
            self::Draft => 'warning',
        };
    }

    public function isEnabled(): bool
    {
        return $this === self::Enabled;
    }

    public function isDisabled(): bool
    {
        return $this === self::Disabled;
    }
}
