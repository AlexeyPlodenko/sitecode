<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Resources\Pages\Tables;

use Alexeyplodenko\Sitecode\Enums\PageState;
use Alexeyplodenko\Sitecode\Filament\Actions\ContentEditAction;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\PagesResource;
use Alexeyplodenko\Sitecode\Models\Page;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action as TableAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->url(fn (Page $record): string => PagesResource::getUrl('content', ['record' => $record]))
                    ->openUrlInNewTab(false),
                TextColumn::make('url')->label('URL'),
                ToggleColumn::make('state')
                    ->label('Enabled')
                    ->alignCenter()
                    ->sortable()
                    ->getStateUsing(fn (Page $record): bool => $record->isEnabled())
                    ->updateStateUsing(function (Page $record, bool $state): PageState {
                        $record->state = $state ? PageState::Enabled : PageState::Disabled;
                        $record->save();
                        if ($record->isDisabled()) {
                            $record->invalidateCache();
                        }
                        return $record->state;
                    }),
                IconColumn::make('cache')->label('Cache')->boolean()->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label('Status')
                    ->options([
                        PageState::Enabled->value => 'Enabled',
                        PageState::Disabled->value => 'Disabled',
                    ]),
            ])
            ->recordUrl(fn (Page $record): string => PagesResource::getUrl('content', ['record' => $record]))
            ->recordActions([
                ContentEditAction::make(),
                TableAction::make('properties')
                    ->label('Properties')
                    ->icon(Heroicon::Cog6Tooth)
                    ->url(fn (Page $record): string => PagesResource::getUrl('properties', ['record' => $record]))
                    ->openUrlInNewTab(false),
                DeleteAction::make()
                    ->before(function (Page $record): void {
                        PagesResource::beforeDelete($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enable_pages')
                        ->label('Enable Pages')
                        ->icon(Heroicon::CheckCircle)
                        ->action(fn (Collection $records) => $records->toQuery()->update(['state' => PageState::Enabled->value])),
                    BulkAction::make('disable_pages')
                        ->label('Disable Pages')
                        ->icon(Heroicon::XCircle)
                        ->action(fn (Collection $records) => $records->each(function (Page $record): void {
                            $record->update(['state' => PageState::Disabled]);
                            $record->invalidateCache();
                        })),
                    BulkAction::make('enable_cache')
                        ->label('Enable Cache')
                        ->icon(Heroicon::CheckCircle)
                        ->action(fn (Collection $records) => $records->toQuery()->update(['cache' => true])),
                    BulkAction::make('disable_cache')
                        ->label('Disable Cache')
                        ->icon(Heroicon::XCircle)
                        ->action(fn (Collection $records) => $records->toQuery()->update(['cache' => false])),
                    BulkAction::make('cache_all')
                        ->label('Cache Pages')
                        ->icon(Heroicon::CheckCircle)
                        ->action(fn (Collection $records) => $records->each(function (Page $record) {
                            $record->cache();
                        })),
                    BulkAction::make('clear_all_cache')
                        ->label('Clear Pages Cache')
                        ->icon(Heroicon::XCircle)
                        ->action(fn (Collection $records) => $records->each(function (Page $record) {
                            $record->invalidateCache();
                        })),
                    DeleteBulkAction::make()
                        ->before(function (Collection $records): void {
                            $records->each(function (Page $record): void {
                                PagesResource::beforeDelete($record);
                            });
                        }),
                ]),
            ]);
    }
}


//->bulkActions([
//    BulkActionGroup::make([
//        BulkAction::make('cache_all')
//            ->label('Cache Pages')
//            ->icon(Heroicon::CheckCircle)
//            ->action(fn (Collection $records) => $records->each(fn (Page $record) => $record->update(['cache' => true]))),
//        BulkAction::make('clear_all_cache')
//            ->label('Clear Pages Cache')
//            ->icon(Heroicon::XCircle)
//            ->action(fn (Collection $records) => $records->each(function (Page $record) {
//                $record->update(['cache' => false]);
//                $record->invalidateCache();
//            })),
//        DeleteBulkAction::make()
//            ->before(function (Collection $records): void {
//                $records->each(function (Page $record): void {
//                    PagesResource::beforeDelete($record);
//                });
//            }),
//    ]),
