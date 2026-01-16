<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages;

use Alexeyplodenko\Sitecode\Filament\Resources\Pages\PagesResource;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\Traits\ChangedFields;
use Alexeyplodenko\Sitecode\Models\Page;
use Filament\Resources\Pages\CreateRecord;

/**
 * @property Page $record
 * @method Page getRecord()
 */
class CreatePages extends CreateRecord
{
    use ChangedFields;

    protected static string $resource = PagesResource::class;

    /**
     * Let's invalidate the cache, just in case it left there from the last time for some reason.
     */
    protected function afterCreate(): void
    {
        $this->record->invalidateCache();
    }

    /**
     * After creating a Page, redirect to the properties editor instead of the list.
     */
    protected function getRedirectUrl(): string
    {
        return PagesResource::getUrl('properties', ['record' => $this->getRecord()]);
    }
}
