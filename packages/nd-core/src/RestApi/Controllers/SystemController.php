<?php

declare(strict_types=1);

namespace NDCore\RestApi\Controllers;

use NDCore\Cache\CacheManager;
use NDCore\Queue\QueueManager;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use WP_REST_Response;

/**
 * Endpoint de salud de la plataforma: `GET /wp-json/nd/v1/system/status`.
 * Documentado en `docs/API.md`.
 */
final class SystemController extends RestController implements RegistersRoutes
{
    public function __construct(
        private readonly CacheManager $cache,
        private readonly QueueManager $queue,
    ) {
    }

    public function registerRoutes(Router $router): void
    {
        $router->get(
            'nd/v1',
            '/system/status',
            [$this, 'status'],
            static fn (): bool => true
        );
    }

    public function status(): WP_REST_Response
    {
        return $this->success([
            'plugin' => 'nd-core',
            'version' => defined('NDCORE_VERSION') ? NDCORE_VERSION : null,
            'cache_driver' => $this->cache->driver()::class,
            'queue' => [
                'pending' => $this->queue->countPending(),
                'failed' => $this->queue->countFailed(),
            ],
            'timestamp' => gmdate('c'),
        ]);
    }
}
