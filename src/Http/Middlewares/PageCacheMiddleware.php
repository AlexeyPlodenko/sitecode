<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Http\Middlewares;

use Alexeyplodenko\Sitecode\Services\PagesCache;
use Alexeyplodenko\Sitecode\Services\PagesRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use RuntimeException;

// @TODO handle the case when $_GET has values
// @TODO to return correct client side cache headers
// @TODO make possible to cache also to memory
// @TODO to serve cached pages with webserver, avoiding hitting PHP completely
// @TODO make which pages to cache configurable in admin panel
// @TODO to clear cache when the page is deleted, content or meta is updated
class PageCacheMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // we cache only GET method responses coming from the website domain
        if (!$request->isMethod('GET') || $request->host() !== appHost()) {
            return $next($request);
        }

        /** @var PagesRepository $pagesRepository */
        $pagesRepository = app(PagesRepository::class);
        $page = $pagesRepository->findByRequestPath($request);
        if (!$page || !$page->cache) {
            return $next($request);
        }

        $pagesCache = app(PagesCache::class);
        $filePath = $pagesCache->getFilePathFromRequest($request);

        // we could not get the file path to use. Let's avoid caching this request
        if (!$filePath) {
            return $next($request);
        }

        // the requested file path is within the /storage/ directory. Let's return it
        if (is_file($filePath)) {
            header('Content-Type: text/html; charset=UTF-8');
//            header('Expires: 0');
//            header('Cache-Control: must-revalidate');
//            header('Pragma: public');
            header('X-Sitecode-Cache: PHP');

            $size = filesize($filePath);
            header("Content-Length: $size");

            readfile($filePath);

            exit;
        }

        $response = $next($request);

        // cache successful responses
        if ($response->isSuccessful()) {
            // ensure the directories exist
            $dirPath = dirname($filePath);
            $isDirCreated = @mkdir($dirPath, 0755, true);

            if (!$isDirCreated) {
                $cachePath = $pagesCache->getCachePath();
                if (!file_exists($cachePath)) {
                    throw new RuntimeException("Cache directory \"$cachePath\" does not exist.");
                }
                if (!is_dir($cachePath)) {
                    throw new RuntimeException("Cache path \"$cachePath\" is not a directory. Probably a file.");
                }
                if (!is_writable($cachePath)) {
                    throw new RuntimeException("Cache directory \"$cachePath\" is not writable by PHP.");
                }
            }

            // write cached data
            file_put_contents($filePath, $response->getContent());
        }

        return $response;
    }
}
