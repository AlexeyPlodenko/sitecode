<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models\Traits;

trait HasFieldName
{
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
}
