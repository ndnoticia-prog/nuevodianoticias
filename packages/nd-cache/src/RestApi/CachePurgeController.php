<?php

declare(strict_types=1);

namespace NDCache\RestApi;

use NDCore\Cache\CacheManager;
use NDCore\Permissions\Capability;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use WP_REST_Response;

/**
 * Purga manual de la caché de página completa, para cuando un cambio no
 * editorial (un widget, un menú, la configuración del tema) deja HTML
 * obsoleto que {@see \NDCache\Invalidation\CacheInvalidator} no cubre por
 * no ser un `save_post`.
 */
final class CachePurgeController extends RestController implements RegistersRoutes {

	public function __construct( private readonly CacheManager $cache ) {
	}

	public function registerRoutes( Router $router ): void {
		$router->post(
			'nd/v1',
			'/cache/purge',
			array( $this, 'purge' ),
			static fn (): bool => current_user_can( Capability::MANAGE_ND_SETTINGS )
		);
	}

	public function purge(): WP_REST_Response {
		$this->cache->flush();

		return $this->success( array( 'data' => array( 'purged' => true ) ) );
	}
}
