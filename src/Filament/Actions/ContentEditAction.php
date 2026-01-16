<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Contracts\HasActions;
use function call_user_func;

class ContentEditAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'content';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Content');
        $this->color('primary');
        $this->icon(Heroicon::DocumentText);
        $this->url(function (Model $record, HasActions $livewire): string {
            $resource = $livewire::getResource();

            return call_user_func([$resource, 'getUrl'], 'content', ['record' => $record]);
        });
        $this->openUrlInNewTab(false);
    }
}
