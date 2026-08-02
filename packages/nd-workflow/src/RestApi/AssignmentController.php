<?php

declare(strict_types=1);

namespace NDWorkflow\RestApi;

use NDCore\Permissions\Capability;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use NDWorkflow\Assignments\AssignmentManager;
use WP_REST_Request;
use WP_REST_Response;

final class AssignmentController extends RestController implements RegistersRoutes {

	public function __construct( private readonly AssignmentManager $assignments ) {
	}

	public function registerRoutes( Router $router ): void {
		$permission = static fn (): bool => current_user_can( Capability::EDIT_ND_WORKFLOW );
		$idArg      = array(
			'id' => array(
				'type'     => 'integer',
				'required' => true,
			),
		);

		$router->post(
			'nd/v1',
			'/workflow/posts/(?P<id>\d+)/assignment',
			array( $this, 'assign' ),
			$permission,
			$idArg + array(
				'user_id' => array(
					'type'     => 'integer',
					'required' => true,
				),
			)
		);

		$router->delete( 'nd/v1', '/workflow/posts/(?P<id>\d+)/assignment', array( $this, 'unassign' ), $permission, $idArg );
	}

	public function assign( WP_REST_Request $request ): WP_REST_Response {
		$postId = (int) $request->get_param( 'id' );
		$userId = (int) $request->get_param( 'user_id' );

		$this->assignments->assign( $postId, $userId );

		return $this->success(
			array(
				'data' => array(
					'post_id'     => $postId,
					'assigned_to' => $userId,
				),
			)
		);
	}

	public function unassign( WP_REST_Request $request ): WP_REST_Response {
		$this->assignments->unassign( (int) $request->get_param( 'id' ) );

		return $this->success( array(), 204 );
	}
}
