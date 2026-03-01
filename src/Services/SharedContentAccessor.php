<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use Alexeyplodenko\Sitecode\Models\SharedContent;
use Illuminate\Database\Eloquent\Builder;
use Alexeyplodenko\Sitecode\Services\ContentAccessor;

// @TODO should this be Repository?
class SharedContentAccessor
{
    protected bool $isPreloadAll = true;
    protected array $sharedContentCache;

    public function has(string|array $title): bool
    {
        return (bool)$this->get($title);
    }

    public function get(string|array $title): ?string
    {
        $titleNormalized = ContentAccessor::normalizeTitle($title);

        if (!isset($this->sharedContentCache[$titleNormalized])) {
            $query = SharedContent::query();
            if ($this->isPreloadAll) {
                $this->sharedContentCache = $query
                                                ->select(['title', 'content'])
                                                ->pluck('content', 'title')
                                                ->toArray();

            } else {
                $this->sharedContentCache[$titleNormalized] = $query
                                                                ->select(['content'])
                                                                ->where('title', $titleNormalized)
                                                                ->first()
                                                                ?->content;
            }
        }

        return $this->sharedContentCache[$titleNormalized] ?? null;
    }
}
