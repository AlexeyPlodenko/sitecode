<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages;

use Alexeyplodenko\Sitecode\Filament\Actions\ContentEditAction;
use Alexeyplodenko\Sitecode\Filament\Actions\CopyPageAction;
use Alexeyplodenko\Sitecode\Filament\Actions\CreatePageAction;
use Alexeyplodenko\Sitecode\Filament\Actions\ViewAction;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\PagesResource;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\Traits\ChangedFields;
use Alexeyplodenko\Sitecode\Models\Page;
use Alexeyplodenko\Sitecode\Services\PagesCache;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property Page $record
 * @method Page getRecord()
 */
class EditProperties extends EditRecord
{
    use ChangedFields;

    protected static string $resource = PagesResource::class;
    protected static ?string $title = 'Edit Properties';
    protected static ?string $breadcrumb = 'Edit Properties';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('View page')->icon(Heroicon::Eye),
            ContentEditAction::make()->label('Content')->icon(Heroicon::OutlinedPencilSquare),
            DeleteAction::make()->label('Delete page')->icon(Heroicon::OutlinedTrash),
            CreatePageAction::make(),
            CopyPageAction::make()->to('properties'),
        ];
    }

    /**
     * Hook that is called after the form data is saved.
     */
    public function afterSave(): void
    {
        $oldData = $this->original;
        $newData = $this->form->getState();

        unset($oldData['content']);

        $changed = $this->getChangedFields($oldData, $newData);

        if (in_array('url', $changed) || in_array('cache', $changed)) {
            $filePath = app(PagesCache::class)->getFilePathFromPageUrl($oldData['url']);
            if ($filePath) {
                $this->record->invalidateCache($filePath);
            }
        }
    }
}
