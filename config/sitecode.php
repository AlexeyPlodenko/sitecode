<?php

return [
    'disk' => env('SITECODE_DISK', 'sitecode_public_media'),
    'admin' => [
        'url' => env('SITECODE_ADMIN_URL', env('APP_URL')),
    ],
    'route' => [
        'domain' => env('SITECODE_DOMAIN', appHost()),
        'middleware' => [
            'web',
            \Alexeyplodenko\Sitecode\Http\Middlewares\PageCacheMiddleware::class,
        ],
        'prefix' => '',
    ],
    'views' => null,
];
