<?php

declare(strict_types=1);

namespace NDAnalytics\RestApi;

use NDAnalytics\Repository\AnalyticsRepository;
use NDCore\Permissions\Capability;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Panel editorial de analítica (nd/v1/analytics/*). No hay interfaz visual
 * en esta versión: expone los datos para que un futuro panel de admin (o
 * cualquier cliente autenticado con la capacidad adecuada) los consuma.
 */
final class AnalyticsController extends RestController implements RegistersRoutes {

	public function __construct( private readonly AnalyticsRepository $repository ) {
	}

	public function registerRoutes( Router $router ): void {
		$permission = static fn (): bool => current_user_can( Capability::VIEW_ND_ANALYTICS );

		$router->get(
			'nd/v1',
			'/analytics/top-posts',
			array( $this, 'topPosts' ),
			$permission,
			array(
				'days'  => array(
					'type'     => 'integer',
					'required' => false,
				),
				'limit' => array(
					'type'     => 'integer',
					'required' => false,
				),
			)
		);

		$router->get(
			'nd/v1',
			'/analytics/active-now',
			array( $this, 'activeNow' ),
			$permission,
			array(
				'minutes' => array(
					'type'     => 'integer',
					'required' => false,
				),
			)
		);

		$router->get(
			'nd/v1',
			'/analytics/top-authors',
			array( $this, 'topAuthors' ),
			$permission,
			array(
				'days' => array(
					'type'     => 'integer',
					'required' => false,
				),
			)
		);

		$router->get(
			'nd/v1',
			'/analytics/top-categories',
			array( $this, 'topCategories' ),
			$permission,
			array(
				'days' => array(
					'type'     => 'integer',
					'required' => false,
				),
			)
		);

		$router->get(
			'nd/v1',
			'/analytics/posts/(?P<id>\d+)/ctr',
			array( $this, 'ctr' ),
			$permission,
			array(
				'id'   => array(
					'type'     => 'integer',
					'required' => true,
				),
				'days' => array(
					'type'     => 'integer',
					'required' => false,
				),
			)
		);
	}

	public function topPosts( WP_REST_Request $request ): WP_REST_Response {
		$days  = $this->intParam( $request, 'days', 7 );
		$limit = $this->intParam( $request, 'limit', 10 );

		return $this->success( array( 'data' => $this->repository->topPosts( $days, $limit ) ) );
	}

	public function activeNow( WP_REST_Request $request ): WP_REST_Response {
		$minutes = $this->intParam( $request, 'minutes', 5 );

		return $this->success( array( 'data' => $this->repository->activeNow( $minutes ) ) );
	}

	public function topAuthors( WP_REST_Request $request ): WP_REST_Response {
		$days = $this->intParam( $request, 'days', 30 );

		return $this->success( array( 'data' => $this->repository->topAuthors( $days ) ) );
	}

	public function topCategories( WP_REST_Request $request ): WP_REST_Response {
		$days = $this->intParam( $request, 'days', 30 );

		return $this->success( array( 'data' => $this->repository->topCategories( $days ) ) );
	}

	public function ctr( WP_REST_Request $request ): WP_REST_Response {
		$postId = $this->intParam( $request, 'id', 0 );
		$days   = $this->intParam( $request, 'days', 30 );

		return $this->success( array( 'data' => $this->repository->ctrForPost( $postId, $days ) ) );
	}

	private function intParam( WP_REST_Request $request, string $name, int $default ): int {
		$value = $request->get_param( $name );

		return $value !== null ? (int) $value : $default;
	}
}
