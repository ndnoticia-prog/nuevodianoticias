<?php

declare(strict_types=1);

namespace NDSearch\RestApi;

use NDCore\Permissions\Capability;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use NDSearch\Indexing\SearchIndexer;
use NDSearch\Repository\SearchIndexRepository;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Panel de administración del índice de búsqueda: estadísticas, contenido
 * indexado reciente, una consulta de prueba contra el índice, y
 * reconstrucción manual bajo demanda ({@see SearchIndexer::reindexAll()}).
 */
final class SearchController extends RestController implements RegistersRoutes {

	public function __construct(
		private readonly SearchIndexRepository $repository,
		private readonly SearchIndexer $indexer,
	) {
	}

	public function registerRoutes( Router $router ): void {
		$permission = static fn (): bool => current_user_can( Capability::MANAGE_ND_SETTINGS );

		$router->get( 'nd/v1', '/search/stats', array( $this, 'stats' ), $permission );

		$router->get(
			'nd/v1',
			'/search/recent',
			array( $this, 'recent' ),
			$permission,
			array(
				'limit' => array(
					'type'     => 'integer',
					'required' => false,
				),
			)
		);

		$router->get(
			'nd/v1',
			'/search/query',
			array( $this, 'query' ),
			$permission,
			array(
				'q'     => array(
					'type'     => 'string',
					'required' => true,
				),
				'limit' => array(
					'type'     => 'integer',
					'required' => false,
				),
			)
		);

		$router->post( 'nd/v1', '/search/reindex', array( $this, 'reindex' ), $permission );
	}

	public function stats(): WP_REST_Response {
		return $this->success( array( 'data' => array( 'indexed' => $this->repository->count() ) ) );
	}

	public function recent( WP_REST_Request $request ): WP_REST_Response {
		$limit = $this->intParam( $request, 'limit', 20 );

		return $this->success( array( 'data' => $this->repository->recent( $limit ) ) );
	}

	public function query( WP_REST_Request $request ): WP_REST_Response {
		$query = trim( (string) $request->get_param( 'q' ) );
		$limit = $this->intParam( $request, 'limit', 20 );

		$results = array_map(
			static fn ( int $postId ): array => array(
				'post_id' => $postId,
				'title'   => get_the_title( $postId ),
			),
			$this->repository->search( $query, $limit )
		);

		return $this->success( array( 'data' => $results ) );
	}

	public function reindex(): WP_REST_Response {
		return $this->success( array( 'data' => array( 'reindexed' => $this->indexer->reindexAll() ) ) );
	}

	private function intParam( WP_REST_Request $request, string $name, int $default ): int {
		$value = $request->get_param( $name );

		return $value !== null ? (int) $value : $default;
	}
}
