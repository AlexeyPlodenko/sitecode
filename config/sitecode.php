<?php

return [
    'disk' => env('SITECODE_DISK', 'sitecode_public_media'),
    'admin' => [
        'url' => env('SITECODE_ADMIN_URL', env('APP_URL')),
    ]
];
