<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Services;

use Alexeyplodenko\Sitecode\Models\Page;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Illuminate\Http\Request;

class PagesRepository extends BaseRepository
{
    use CacheableRepository;

    public function model(): string
    {
        return Page::class;
    }

    public function findByUrl(string $url): ?Page
    {
        return $this->findWhere(['url' => $url])->first();
    }

    public function findByRequestPath(Request $request): ?Page
    {
        $url = '/'. trim($request->path(), '/');

        return $this->findByUrl($url);
    }
}
