<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages;

use Alexeyplodenko\Sitecode\Filament\Resources\Pages\PagesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListPages extends ListRecords
{
    protected static string $resource = PagesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Create page')->icon(Heroicon::PlusCircle),
        ];
    }
}
