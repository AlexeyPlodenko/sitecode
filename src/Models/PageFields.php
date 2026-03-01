<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models;

use Alexeyplodenko\Sitecode\Models\Traits\HasFieldName;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use RuntimeException;

class PageFields
{
    use HasFieldName;

    protected static int $id = 0;
    protected bool $isShared = false;
    protected bool $repeated = false;
    protected ?string $repeaterName = null;
    protected Section|Repeater $filamentComponent;
    protected ?string $label = null;

    /**
     * @var PageField[]
     */
    protected array $fieldsFlat;

    /**
     * @var array<string, string>
     */
    protected array $titleLow = [];

    /** @var (PageField|PageFields)[] */
    protected array $fields = [];

    public function __construct(protected ?string $title = null, protected ?PageFields $parent = null)
    {
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

    public function concat(PageFields $pageFields): static
    {
        $this->fields = array_merge($this->fields, $pageFields->getFields());

        return $this;
    }

    /**
     * @return PageField[]
     */
    public function getSharedFields(): array
    {
        $res = [];
        foreach ($this->getFields() as $field) {
            if ($field->isShared()) {
                $res[] = $field;
            }
        }

        return $res;
    }

    public function getFieldByFullTitle(string $fullTitle): ?PageField
    {
        // @TODO use recursive iterator here
        foreach ($this->getFieldsFlat() as $field) {
            if ($field->getFullTitle() === $fullTitle) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return PageField[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function isShared(): bool
    {
        if ($this->parent && $this->parent->isShared()) {
            return true;
        }

        return $this->isShared;
    }

    public function setIsShared(bool $flag = true): static
    {
        $this->isShared = $flag;

        return $this;
    }

    public function isRepeated(): bool
    {
        return $this->repeated;
    }

    public function useRepeater(bool $flag = true, ?string $name = null): static
    {
        $this->repeated = $flag;
        $this->repeaterName = $name;

        return $this;

    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function makeField(string $title): PageField
    {
        $newField = PageField::fromTitle($title, $this);
        $newField->setIsShared($this->isShared);
        $newField->setEditorTextInput();

        $this->throwIfTitleExists($title);

        $this->fields[] = $newField;

        return $newField;
    }

    public function makeFieldGroup(string $title): PageFields
    {
        $fieldsGroup = new PageFields($title, $this);
        $fieldsGroup->setIsShared($this->isShared);

        $this->throwIfTitleExists($title);

        $this->fields[] = $fieldsGroup;

        return $fieldsGroup;
    }

    public function getFilamentComponent(): Section|Repeater
    {
        if (!isset($this->filamentComponent)) {
            $schema = [];
            foreach ($this->fields as $field) {
                $schema[] = $field->getFilamentComponent();
            }

            $id = static::$id++;
            $fCompId = "page_fields_$id";

            if ($this->repeated) {
                $repeater = Repeater::make($this->getFullTitle());
                $repeater->name($this->repeaterName ?? $this->getLabel() ?? $this->title);
                $repeater->schema($schema);
                $repeater->columnSpanFull();

                $repeaterSchema = [$repeater];
            }

            $this->filamentComponent = Section::make();
            $this->filamentComponent->heading($this->getLabel() ?? $this->title);
            $this->filamentComponent->id("page_fields_$id");
            $this->filamentComponent->compact();
            $this->filamentComponent->collapsible();
            $this->filamentComponent->persistCollapsed();
            $this->filamentComponent->columns(1);
            $this->filamentComponent->schema($repeaterSchema ?? $schema);
        }

        return $this->filamentComponent;
    }

    public function getFieldsFlat(): array
    {
        if (!isset($this->fieldsFlat)) {
            $this->fieldsFlat = [];

            foreach ($this->fields as $field) {
                if ($field instanceof PageField) {
                    $this->fieldsFlat[] = $field;
                } elseif ($field instanceof PageFields) {
                    $this->fieldsFlat = array_merge($this->fieldsFlat, $field->getFieldsFlat());
                } else {
                    throw new \RuntimeException('Unknown type of field.');
                }
            }

            return $this->fieldsFlat;
        }

        return $this->fieldsFlat;
    }

    /**
     * @return PageField[]
     */
    public function getSharedFieldsFlat(): array
    {
        $fields = $this->getFieldsFlat();
        return array_filter($fields, fn ($field) => $field->isShared());
    }

    public function getFullTitlesFlat(): array
    {
        $titles = [];
        foreach ($this->fields as $field) {
            if ($field instanceof PageField) {
                $titles[] = $field->getFullTitle();
            } else {
                $titles = array_merge($titles, $field->getFullTitlesFlat());
            }
        }
        return $titles;
    }

    protected function throwIfTitleExists(string $title): void
    {
        if (!isset($this->titleLow[$title])) {
            $this->titleLow[$title] = mb_strtolower($title);
        }

        $titleLow = $this->titleLow[$title];
        foreach ($this->fields as $field) {
            if (mb_strtolower($field->getTitle()) === $titleLow) {
                throw new RuntimeException("There is already a field with the name \"$title\".");
            }
        }
    }
}
