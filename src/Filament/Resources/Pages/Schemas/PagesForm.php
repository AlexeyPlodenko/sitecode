<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Resources\Pages\Schemas;

use Alexeyplodenko\Sitecode\Filament\Resources\Pages\PagesResource;
use Alexeyplodenko\Sitecode\Models\Page;
use Alexeyplodenko\Sitecode\Services\Views;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Str;

class PagesForm
{
    public static function configure(Schema $schema): Schema
    {
        $views = (new Views())->withAdminPagesOnly()->forUserSelect();

        return $schema
            ->components([
                TextInput::make('title')->maxLength(255)->required(),

                TextInput::make('url')
                    ->label('URL')
                    ->maxLength(255)
                    ->required()
                    ->placeholder('/your-path-here')
                    ->hint('Must start with /')
                    // 1. Validation: Ensures manual entry also starts with /
                    ->startsWith('/')
                    ->suffixAction(
                        Action::make('slugify')
                            ->icon('heroicon-m-arrow-path')
                            ->tooltip('Generate URL from title')
                            ->modalHidden(fn (Get $get) => blank($get('url')))
                            ->requiresConfirmation()
                            ->modalHeading('Overwrite URL?')
                            ->action(function (Set $set, Get $get) {
                                $title = $get('title');

                                if (filled($title)) {
                                    $slug = Str::slug($title);

                                    // 2. Logic: Prepend / and ensure no double slashes
                                    $set('url', '/' . ltrim($slug, '/'));
                                }
                            })
                    ),

                Group::make()
                    ->schema([
                        Select::make('view')->options($views)->required(),
                        Checkbox::make('cache'),
                        TextEntry::make('is_cached')
                            ->label('Cache Status')
                            ->hint('The page is cached when first accessed on the website.')
                            ->getStateUsing(function (?Page $record) {
                                return $record?->isCached();
                            })
                            ->formatStateUsing(function (?Page $record) {
                                if (!$record) {
                                    return null;
                                }

                                $msg = $record->isCached()
                                    ? 'Cached. <a href="'. PagesResource::getUrl('clear-cache', ['record' => $record]) .'" style="text-decoration: underline;">Clear now</a>'
                                    : 'Not cached. <a href="'. PagesResource::getUrl('cache', ['record' => $record]) .'" style="text-decoration: underline;">Cache now</a>';

                                return new HtmlString($msg);
                            })
                            ->hidden(function (?Page $record): bool {
                                return !$record?->cache;
                            })
                    ])
                    ->columns(1)
                    ->columnSpan(1),
            ]);
    }
}
