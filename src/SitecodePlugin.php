<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode;

use Alexeyplodenko\Sitecode\Filament\Resources\Pages\PagesResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class SitecodePlugin implements Plugin
{
    public function getId(): string
    {
        return 'alexeyplodenko-sitecode';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PagesResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
