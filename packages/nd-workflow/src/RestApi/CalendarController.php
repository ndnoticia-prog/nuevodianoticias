<?php

declare(strict_types=1);

namespace NDWorkflow\RestApi;

use NDCore\Permissions\Capability;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use NDWorkflow\Calendar\CalendarRepository;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

final class CalendarController extends RestController implements RegistersRoutes {

	public function __construct( private readonly CalendarRepository $calendar ) {
	}

	public function registerRoutes( Router $router ): void {
		$permission = static fn (): bool => current_user_can( Capability::EDIT_ND_WORKFLOW );

		$router->get(
			'nd/v1',
			'/workflow/calendar',
			array( $this, 'index' ),
			$permission,
			array(
				'year'  => array(
					'type'     => 'integer',
					'required' => true,
				),
				'month' => array(
					'type'     => 'integer',
					'required' => true,
				),
			)
		);

		$router->patch(
			'nd/v1',
			'/workflow/posts/(?P<id>\d+)/schedule',
			array( $this, 'reschedule' ),
			$permission,
			array(
				'id'   => array(
					'type'     => 'integer',
					'required' => true,
				),
				'date' => array(
					'type'     => 'string',
					'required' => true,
				),
			)
		);
	}

	public function index( WP_REST_Request $request ): WP_REST_Response {
		$year  = (int) $request->get_param( 'year' );
		$month = (int) $request->get_param( 'month' );

		return $this->success( array( 'data' => $this->calendar->postsForMonth( $year, $month ) ) );
	}

	/**
	 * Mueve un artículo a otro día del calendario editorial (arrastrar y
	 * soltar): reutiliza post_date de WordPress core en vez de un campo de
	 * fecha propio, para que siga siendo el sistema de programación nativo
	 * (wp_update_post(), wp-cron para publicar en el momento programado).
	 */
	public function reschedule( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$postId = (int) $request->get_param( 'id' );
		$date   = (string) $request->get_param( 'date' );

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) !== 1 ) {
			return $this->error( 'nd_workflow_invalid_date', __( 'La fecha debe tener el formato AAAA-MM-DD.', 'nd-workflow' ), 422 );
		}

		$post = get_post( $postId );

		if ( ! $post instanceof WP_Post ) {
			return $this->error( 'nd_workflow_post_not_found', __( 'Artículo no encontrado.', 'nd-workflow' ), 404 );
		}

		$currentTime = gmdate( 'H:i:s', strtotime( $post->post_date ) !== false ? strtotime( $post->post_date ) : 0 );

		$updated = wp_update_post(
			array(
				'ID'            => $postId,
				'post_date'     => "{$date} {$currentTime}",
				'edit_date'     => true,
				'post_date_gmt' => get_gmt_from_date( "{$date} {$currentTime}" ),
			),
			true
		);

		if ( $updated instanceof WP_Error ) {
			return $this->error( 'nd_workflow_reschedule_failed', $updated->get_error_message(), 500 );
		}

		return $this->success(
			array(
				'data' => array(
					'id'   => $postId,
					'date' => $date,
				),
			)
		);
	}
}
