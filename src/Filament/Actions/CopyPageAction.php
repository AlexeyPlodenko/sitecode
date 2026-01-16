<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Actions;

use Alexeyplodenko\Sitecode\Models\Page;
use Alexeyplodenko\Sitecode\Services\PageCloner;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class CopyPageAction extends Action
{
    protected string $redirectTo = 'properties';

    public static function getDefaultName(): ?string
    {
        return 'copyPage';
    }

    public function to(string $pageKey): static
    {
        $this->redirectTo = $pageKey;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Copy current page');
        $this->color('info');
        $this->icon(Heroicon::DocumentDuplicate);

        // Ask for confirmation before performing the copy
        $this->requiresConfirmation();
        $this->modalHeading('Copy this page?');
        $this->modalDescription('A new page will be created with the same properties and non-image content. Image/file fields will not be copied.');
        $this->modalSubmitActionLabel('Yes, copy page');
        $this->modalCancelActionLabel('Cancel');

        $this->action(function (Model $record, HasActions $livewire) {
            /** @var Page $record */
            $new = app(PageCloner::class)->cloneWithoutImages($record);

            $resource = $livewire::getResource();
            $url = $resource::getUrl($this->redirectTo, ['record' => $new]);

            return redirect()->to($url);
        });
    }
}
