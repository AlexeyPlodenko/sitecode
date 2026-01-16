<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models;

use Filament\Schemas\Components\Section;
use RuntimeException;

class PageFields
{
    protected static int $id = 0;
    protected bool $isShared = false;
    protected Section $filamentComponent;

    /**
     * @var array<string, string>
     */
    protected array $titleLow = [];

    /** @var (PageField|PageFields)[] */
    protected array $fields = [];

    public function __construct(protected ?string $title = null, protected ?PageFields $parent = null)
    {
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
        return array_find($this->getFieldsFlat(), fn($field) => $field->getFullTitle() === $fullTitle);

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getFullTitle(): ?string
    {
        $parentFullTitle = $this->parent?->getFullTitle();
        return $parentFullTitle ? $parentFullTitle . ": $this->title" : $this->title;
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

    public function getFilamentComponent(): Section
    {
        if (!isset($this->filamentComponent)) {
            $subComponents = [];
            foreach ($this->fields as $field) {
                $subComponents[] = $field->getFilamentComponent();
            }

            $id = static::$id++;
            $this->filamentComponent = Section::make();
            $this->filamentComponent->view('filament.components.section');
            $this->filamentComponent->id("page_fields_$id");
            $this->filamentComponent->label($this->title);
            $this->filamentComponent->compact();
            $this->filamentComponent->collapsible();
            $this->filamentComponent->persistCollapsed();
            $this->filamentComponent->columns(1);
            $this->filamentComponent->schema($subComponents);
        }

        return $this->filamentComponent;
    }

    /**
     * @return PageField[]
     */
    public function getFieldsFlat(): array
    {
        $fields = [];
        foreach ($this->fields as $field) {
            if ($field instanceof PageField) {
                $fields[] = $field;
            } else {
                $fields = array_merge($fields, $field->getFieldsFlat());
            }
        }
        return $fields;
    }

    /**
     * @return PageField[]
     */
    public function getSharedFieldsFlat(): array
    {
        $fields = $this->getFieldsFlat();
        return array_filter($fields, fn (PageField $field) => $field->isShared());
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
