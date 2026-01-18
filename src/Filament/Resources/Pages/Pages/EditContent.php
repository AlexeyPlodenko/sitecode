<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Resources\Pages\Pages;

use Alexeyplodenko\Sitecode\Enums\ContentEditor;
use Alexeyplodenko\Sitecode\Filament\Actions\CreatePageAction;
use Alexeyplodenko\Sitecode\Filament\Actions\ViewAction;
use Alexeyplodenko\Sitecode\Filament\Actions\CopyPageAction;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\PagesResource;
use Alexeyplodenko\Sitecode\Filament\Resources\Pages\Traits\ChangedFields;
use Alexeyplodenko\Sitecode\Models\PageField;
use Alexeyplodenko\Sitecode\Models\Page;
use Alexeyplodenko\Sitecode\Models\PageFields;
use Alexeyplodenko\Sitecode\Models\SharedContent;
use Config;
use DB;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Storage;
use Throwable;

/**
 * @property Page $record
 * @method Page getRecord()
 */
class EditContent extends EditRecord
{
    use ChangedFields;

    protected static string $resource = PagesResource::class;
    protected static ?string $title = 'Edit Content';
    protected static ?string $breadcrumb = 'Edit Content';
    protected array $mutatedData;

    /**
     * @var array[]
     */
    protected array $sharedContent = [];

    public function form(Schema $schema): Schema
    {
        $this->fixMediaUrl();

        $components = [];
        foreach ($this->record->getPageFields()->getFields() as $field) {
            if ($field instanceof PageFields) {
                $component = $field->getFilamentComponent();

            } elseif ($field instanceof PageField) {
                $component = $field->getFilamentComponent();

            } else {
                throw new RuntimeException('Unknown instance.');
            }

            $components[] = $component;
        }

        $form = $this->defaultForm($schema);
        $form->columns(1);
        $form->components($components);
        return $form;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('View page')->icon(Heroicon::Eye),
            EditAction::make()
                ->label('Properties')
                ->url(fn () => PagesResource::getUrl('properties', ['record' => $this->record]))
                ->icon(Heroicon::Cog6Tooth),
            DeleteAction::make()->label('Delete page')->icon(Heroicon::OutlinedTrash),
            CreatePageAction::make(),
            CopyPageAction::make()->to('content'),
        ];
    }

    protected function getSharedFieldFullTitles(): array
    {
        $sharedFieldTitles = [];
        foreach ($this->record->getPageFields()->getSharedFieldsFlat() as $field) {
            $sharedFieldTitles[] = $field->getFullTitle();
        }
        return $sharedFieldTitles;
    }

    protected function getSharedContent(): array
    {
        $sharedContent = [];

        $sharedFieldTitles = $this->getSharedFieldFullTitles();
        if ($sharedFieldTitles) {
            $sharedContentItems = SharedContent::query()
                                                ->select(['title', 'content'])
                                                ->whereIn('title', $sharedFieldTitles)
                                                ->get();
            foreach ($sharedContentItems as $sharedContentItem) {
                /** @var SharedContent $sharedContentItem */
                $sharedContent[$sharedContentItem->title] = $sharedContentItem->content;
            }
        }

        return $sharedContent;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data += $this->getSharedContent();

        if ($data['content']) {
            foreach ($data['content'] as $key => $value) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    protected function removeHtmlWithoutContent(?string $data): ?string
    {
        return $data === '<p></p>' ? '' : $data;
    }

    protected function normalizeFormData(array $data): array
    {
        foreach ($data as &$value) {
            $value = $this->removeHtmlWithoutContent($value);
        }
        unset($value);

        return $data;
    }

    // @TODO remove later
    // Fixes Filament TipTap WYSIWYG editor bug:
    // https://github.com/filamentphp/filament/issues/16829
    // https://github.com/ueberdosis/tiptap-php/issues/73
    protected function removeTargetBlank(?string $content): ?string
    {
        if (!$content) {
            return $content;
        }

        return preg_replace('/ target=["\']_blank["\']/i', '', $content);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->normalizeFormData($data);

        $this->sharedContent = [];
        $pageContent = [];
        foreach ($this->record->getPageFields()->getFieldsFlat() as $field) {
            $fieldName = $field->getFieldName();
            $fieldContent = $data[$fieldName] ?? null;

            if ($field->getEditor() === ContentEditor::WYSIWYG) {
                $fieldContent = $this->removeTargetBlank($fieldContent);
            }

            if ($field->isShared()) {
                $this->sharedContent[$fieldName] = ['title' => $fieldName, 'content' => $fieldContent];
            } else {
                $pageContent[$fieldName] = $fieldContent;
            }

            $data[$fieldName] = $fieldContent;
        }

        $this->mutatedData = $data;

        // $pageContent already contains updated $fieldContent
        return $pageContent;
    }

    /**
     * @param Model $record
     * @param array<string,mixed> $data
     * @return Model
     * @throws Throwable
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        DB::transaction(function () use ($record, $data) {
            $record->content = $data;
            $record->save();

            if ($this->sharedContent) {
                $existingContent = SharedContent::query()
                                                ->select(['id', 'title'])
                                                ->whereIn('title', array_keys($this->sharedContent))
                                                ->lockForUpdate()
                                                ->get();

                foreach ($existingContent as $item) {
                    /** @var SharedContent $item */
                    $item->content = $this->sharedContent[$item->title]['content'] ?? null;
                    $item->save();

                    unset($this->sharedContent[$item->title]);
                }

                if ($this->sharedContent) {
                    SharedContent::query()->insert($this->sharedContent);
                }
            }

            // Laravel's upsert() (MySQL's INSERT ON DUPLICATE KEY UPDATE) keeps table's ID growing with every update.
            // Let's use a custom solution above
//            SharedContent::query()->upsert($this->sharedContent, ['title'], ['content']);
        });

        return $record;
    }

    /**
     * Since we might be using a separate domain for admin panel, and to avoid CORS issues,
     * and avoid adjusting the web server configuration,
     * let's set the Disk URL to admin URL for images to load correctly.
     */
    protected function fixMediaUrl(): void
    {
        $websiteUrl = config('app.url');
        $adminUrl = config('sitecode.admin.url');
        if ($websiteUrl === $adminUrl) {
            // Nothing to do
            return;
        }

        // Use the Config facade to change the settings in Laravel's configuration repository.
        $diskName = config('sitecode.disk');
        $disk = config("filesystems.disks.$diskName");


        // Replace website base URL with admin base URL
        $websiteUrlLen = mb_strlen($websiteUrl);
        $disk['url'] = $adminUrl . mb_substr($disk['url'], $websiteUrlLen);

        Config::set("filesystems.disks.$diskName", $disk);

        // Force the manager to forget the old 'public_media_uploads' disk instance.
        // The next time Storage::disk('public_media_uploads') is called, a brand new
        // FilesystemAdapter instance will be created using the new config.
        Storage::forgetDisk($diskName);
    }

    protected function beforeSave(): void
    {
        $this->original = $this->record->toArray()['content'] ?? [];
        $this->original += $this->getSharedContent();
    }

    /**
     * Hook that is called after the form data is saved.
     */
    protected function afterSave(): void
    {
        $oldData = $this->original;
        $newData = $this->mutatedData;

        $changed = $this->getChangedFields($oldData, $newData);
        if ($changed) {
            $this->record->invalidateCache();
        }
    }
}
