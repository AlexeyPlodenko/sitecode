<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Resources\Pages;

use Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages\CachePage;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages\ClearPageCache;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages\CreatePages;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages\EditContent;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages\EditProperties;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages\ListPages;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\Schemas\PagesForm;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\Tables\PagesTable;
use Alexeyplodenko\Sitecode\Models\Page;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PagesResource extends Resource
{
    protected static ?string $model = Page::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return PagesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePages::route('/create'),
            'properties' => EditProperties::route('/{record}/properties'),
            'content' => EditContent::route('/{record}/content'),
            'clear-cache' => ClearPageCache::route('/{record}/clear-cache'),
            'cache' => CachePage::route('/{record}/cache'),
        ];
    }

    /**
     * Hook called before a page record is deleted via Filament actions.
     * Add your custom logic inside this method.
     */
    public static function beforeDelete(Page $record): void
    {
        $record->invalidateCache();
    }
}
