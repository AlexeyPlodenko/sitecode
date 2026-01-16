<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Resources\Traits;

use Closure;
use Filament\Notifications\Notification;

trait Errorable
{
    protected function error(string|Closure|null $body, string|Closure|null $title): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->danger()
            ->persistent()
            ->send();
    }
}
