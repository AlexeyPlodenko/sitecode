<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models;

use Alexeyplodenko\Sitecode\Enums\ContentEditor;
use Filament\Actions\Action;
use Filament\Forms\Components\Field as FilamentField;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class PageField
{
    protected ContentEditor $editor;
    protected FilamentField $filamentComponent;
    protected bool $isShared;
    protected string $hint;
    protected ?string $label = null;

    public function __construct(protected string $title, protected ?PageFields $parent = null)
    {
    }

    public static function fromTitle(string $title, ?PageFields $parent = null): static
    {
        return new static($title, $parent);
    }


    public function label(string|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getHint(): string
    {
        return $this->hint;
    }

    public function setHint(string $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Since Filament converts field names with dots to arrays, lets remove the dots from the field names,
     * to prevent this behavior.
     */
    public function getFieldName(): ?string
    {
        $fullTitle = $this->getFullTitle();
        return $fullTitle ? str_replace('.', '', $fullTitle) : $fullTitle;
    }

    public function getFullTitle(): ?string
    {
        $parentFullTitle = $this->parent?->getFullTitle();
        return $parentFullTitle ? $parentFullTitle . ": $this->title" : $this->title;
    }

    public function getEditor(): ContentEditor
    {
        return $this->editor;
    }

    public function setEditor(ContentEditor $editor): static
    {
        $this->editor = $editor;

        return $this;
    }

    public function setEditorTextInput(): static
    {
        return $this->setEditor(ContentEditor::TextInput);
    }

    public function setEditorTextarea(): static
    {
        return $this->setEditor(ContentEditor::Textarea);
    }

    public function setEditorWysiwyg(): static
    {
        return $this->setEditor(ContentEditor::WYSIWYG);
    }

    public function setEditorFile(): static
    {
        return $this->setEditor(ContentEditor::File);
    }

    public function isEditorWysiwyg(): bool
    {
        return $this->getEditor() === ContentEditor::WYSIWYG;
    }

    public function getFilamentComponent(): FilamentField
    {
        if (!isset($this->filamentComponent)) {
            $this->filamentComponent = match ($this->getEditor()) {
                ContentEditor::TextInput => TextInput::make($this->getFieldName()),

                ContentEditor::Textarea => Textarea::make($this->getFieldName()),

                ContentEditor::WYSIWYG => RichEditor::make($this->getFieldName())
                    ->hintAction(
                        Action::make('editHtml')
                            ->label('Edit HTML')
                            ->icon('heroicon-m-code-bracket')
                            ->fillForm(fn (Get $get) => ['html_code' => $get($this->getFieldName())])
                            ->schema([
                                TextArea::make('html_code')
                                    ->rows(10)
                                    ->rows(20)
                                    ->label('Raw HTML'),
                            ])
                            ->action(function (array $data, Set $set) {
                                $set($this->getFieldName(), $data['html_code']);
                            })
                    )
                    ->fileAttachmentsDisk(config('sitecode.disk')),

                ContentEditor::File => FileUpload::make($this->getFieldName())
                    ->disk(config('sitecode.disk')),
            };

            if (isset($this->hint)) {
                $this->filamentComponent->hint($this->hint);
            }

            $label = $this->getLabel() ?? $this->getTitle();
            if ($this->isShared()) {
                $label .= ' (shared)';
            }
            $this->filamentComponent->label($label);
        }

        return $this->filamentComponent;
    }

    public function setIsShared(bool $isShared = true): static
    {
        $this->isShared = $isShared;

        return $this;
    }

    public function isShared(): bool
    {
        if ($this->parent && $this->parent->isShared()) {
            return true;
        }

        return $this->isShared;
    }
}
