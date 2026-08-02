<?php

declare(strict_types=1);

return [
    /*
     * Drivers disponibles: "transient", "object-cache", "redis".
     */
    'driver' => defined('ND_CACHE_DRIVER') ? ND_CACHE_DRIVER : 'transient',

    'ttl' => 3600,

    'redis' => [
        'host' => defined('ND_REDIS_HOST') ? ND_REDIS_HOST : '127.0.0.1',
        'port' => defined('ND_REDIS_PORT') ? (int) ND_REDIS_PORT : 6379,
        'password' => defined('ND_REDIS_PASSWORD') ? ND_REDIS_PASSWORD : null,
        'database' => defined('ND_REDIS_DATABASE') ? (int) ND_REDIS_DATABASE : 0,
        'prefix' => 'nd_platform:',
    ],
];
