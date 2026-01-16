<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Actions;

use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class CreatePageAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'createPage';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Create a page');
        $this->color('info');
        $this->icon(Heroicon::PlusCircle);

        $this->action(function (Model $record, HasActions $livewire) {
            $resource = $livewire::getResource();
            $url = $resource::getUrl('create');
            return redirect()->to($url);
        });
    }
}
