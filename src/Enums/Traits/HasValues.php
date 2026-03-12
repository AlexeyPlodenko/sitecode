<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Enums\Traits;

trait HasValues
{
    /**
     * Get all values of the backed enum cases.
     * @return array<int, string|int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all names (keys) of the enum cases.
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }
}
