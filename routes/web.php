<?php

use Alexeyplodenko\Sitecode\Http\Middlewares\PageCacheMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('{any}', function () {
    $url = '/'. trim(Request::path(), '/');

    return sitecodeViewFromUrl($url);
})->where('any', '.*')->domain(appHost())->middleware(PageCacheMiddleware::class);
