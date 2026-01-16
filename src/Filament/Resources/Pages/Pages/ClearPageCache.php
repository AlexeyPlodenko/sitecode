<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages;

use Alexeyplodenko\Sitecode\Filament\Resources\Pages\PagesResource;
use Alexeyplodenko\Sitecode\Filament\Resources\Traits\Errorable;
use Alexeyplodenko\Sitecode\Models\Page;
use Filament\Resources\Pages\EditRecord;

/**
 * @property Page $record
 * @method Page getRecord()
 */
class ClearPageCache extends EditRecord
{
    use Errorable;

    protected static string $resource = PagesResource::class;

    public function mount(string|int $record): void
    {
        parent::mount($record);

        $this->cache();
    }

    protected function cache(): void
    {
        $invalidated = $this->getRecord()->invalidateCache();
        if (!$invalidated) {
            $this->error('Either there is no cache or file permissions are misconfigured.', 'Cannot Delete Page Cache');
        }

        $url = redirect()->back()->getTargetUrl();
        $this->redirect($url);
    }
}
