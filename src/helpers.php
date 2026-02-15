<?php declare(strict_types=1);

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View as ViewContract;
use Alexeyplodenko\Sitecode\Services\PagesRepository;
use Alexeyplodenko\Sitecode\Models\Page;

function viewFromPath(string $path): string
{
    $view = preg_replace('/\.blade\.php$/', '', $path);
    return str_replace('/', '.', $view);
}

function go(string|\Illuminate\Http\RedirectResponse $to, bool $exit = true): void
{
    $url = is_object($to) ? $to->getTargetUrl() : $to;
    header("Location: $url");

    if ($exit) {
        exit;
    }
}

function appHost(): ?string
{
    return parse_url(config('app.url'), PHP_URL_HOST);
}

function sitecodeViewFromUrl(string $url, array $data = []): ViewFactory|ViewContract
{
    $pages = app(PagesRepository::class);
    $page = $pages->findByUrl($url);
    if (!$page) {
        abort(404);
    }

    $page->loadSharedContent();

    $view = viewFromPath($page->view);
    return view($view, ['page' => $page, ...$data]);
}

function sitecodeViewFromBlade(string $bladeView, array $data = []): ViewFactory|ViewContract
{
    $page = new Page();
    $page->view = $bladeView;

    $page->loadSharedContent();

    $view = viewFromPath($page->view);
    return view($view, ['page' => $page, ...$data]);
}
