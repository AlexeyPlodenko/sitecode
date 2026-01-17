<?php declare(strict_types=1);

use JetBrains\PhpStorm\NoReturn;

function viewFromPath(string $path): string
{
    $view = preg_replace('/\.blade\.php$/', '', $path);
    return str_replace('/', '.', $view);
}

#[NoReturn]
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
