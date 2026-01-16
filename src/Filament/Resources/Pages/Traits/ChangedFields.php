<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Filament\Resources\Pages\Traits;

trait ChangedFields
{
    /** @var array<string, mixed> */
    private array $original;

    protected function getChangedFields(array $oldData, array $newData): array
    {
        $changed = [];

        // Compute changed fields by comparing with originals captured in beforeSave()
        foreach ($oldData as $key => $old) {
            $new = $newData[$key] ?? null;
            if ($this->normalizeComparisonData($new) !== $this->normalizeComparisonData($old)) {
                $changed[$key] = $key;
            }
        }

        foreach ($newData as $key => $value) {
            if (!array_key_exists($key, $oldData)) {
                $changed[$key] = $key;
            }
        }

        return $changed;
    }

    protected function normalizeComparisonData(mixed $item): string|int|null
    {
        if (!isset($item)) return null;
        if (is_bool($item)) return $item ? 1 : 0;
        if (is_numeric($item)) return (string) +$item;

        return (string) $item;
    }

    protected function beforeSave(): void
    {
        $this->original = $this->record->toArray();
    }
}
