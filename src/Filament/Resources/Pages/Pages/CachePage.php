<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages;

use Alexeyplodenko\Sitecode\Exceptions\Models\Page\CacheDisabled;
use Alexeyplodenko\Sitecode\Exceptions\Models\Page\PageErrored;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\PagesResource;
use Alexeyplodenko\Sitecode\Filament\Resources\Traits\Errorable;
use Alexeyplodenko\Sitecode\Models\Page;
use Filament\Resources\Pages\EditRecord;

/**
 * @property Page $record
 * @method Page getRecord()
 */
class CachePage extends EditRecord
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
        try {
            $this->getRecord()->cache();
        } catch (CacheDisabled) {
            $this->error('The cache is disabled for this page.', 'Cannot Cache Page');
        }

        $url = redirect()->back()->getTargetUrl();
        $this->redirect($url);
    }
}
