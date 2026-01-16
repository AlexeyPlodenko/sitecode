<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Actions;

use Alexeyplodenko\Sitecode\Models\Page;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ViewAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'view';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Content');
        $this->color('primary');
        $this->icon(Heroicon::DocumentText);
        $this->url(function (Page $record): string {
            return $record->makeFullUrl();
        });
        $this->openUrlInNewTab(false);
    }
}
