<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('sitecode.route.domain'),
    'middleware' => config('sitecode.route.middleware'),
    'prefix' => config('sitecode.route.prefix'),
], function () {
    Route::get('{any}', function () {
        $url = '/' . trim(Request::path(), '/');
        return sitecodeViewFromUrl($url);
    })->where('any', '.*');
});
