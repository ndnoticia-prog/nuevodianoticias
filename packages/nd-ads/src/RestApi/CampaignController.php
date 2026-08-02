<?php

declare(strict_types=1);

namespace NDAds\RestApi;

use NDAds\Domain\CampaignType;
use NDAds\Repository\CampaignRepository;
use NDAds\Stats\StatsRepository;
use NDCore\Permissions\Capability;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * CRUD de campañas para el gestor de admin: `active()` de
 * CampaignRepository (la que realmente sirve anuncios) no cambia, este
 * controlador solo cubre la gestión (listar todas, crear, editar, borrar).
 */
final class CampaignController extends RestController implements RegistersRoutes {

	public function __construct(
		private readonly CampaignRepository $campaigns,
		private readonly StatsRepository $stats,
	) {
	}

	public function registerRoutes( Router $router ): void {
		$permission = static fn (): bool => current_user_can( Capability::MANAGE_ND_ADS );
		$idArg      = array(
			'id' => array(
				'type'     => 'integer',
				'required' => true,
			),
		);
		$bodyArgs   = array(
			'name'           => array(
				'type'     => 'string',
				'required' => true,
			),
			'advertiser'     => array(
				'type'     => 'string',
				'required' => true,
			),
			'type'           => array(
				'type'     => 'string',
				'required' => true,
				'enum'     => array_map( static fn ( CampaignType $type ): string => $type->value, CampaignType::cases() ),
			),
			'active'         => array(
				'type'     => 'boolean',
				'required' => false,
			),
			'priority'       => array(
				'type'     => 'integer',
				'required' => false,
			),
			'zones'          => array(
				'type'     => 'array',
				'required' => false,
			),
			'category_slugs' => array(
				'type'     => 'array',
				'required' => false,
			),
			'creative'       => array(
				'type'     => 'object',
				'required' => false,
			),
			'starts_at'      => array(
				'type'     => array( 'string', 'null' ),
				'required' => false,
			),
			'ends_at'        => array(
				'type'     => array( 'string', 'null' ),
				'required' => false,
			),
		);

		$router->get( 'nd/v1', '/ads/campaigns', array( $this, 'index' ), $permission );
		$router->post( 'nd/v1', '/ads/campaigns', array( $this, 'store' ), $permission, $bodyArgs );
		$router->put( 'nd/v1', '/ads/campaigns/(?P<id>\d+)', array( $this, 'update' ), $permission, $idArg + $bodyArgs );
		$router->patch(
			'nd/v1',
			'/ads/campaigns/(?P<id>\d+)/active',
			array( $this, 'toggleActive' ),
			$permission,
			$idArg + array(
				'active' => array(
					'type'     => 'boolean',
					'required' => true,
				),
			)
		);
		$router->delete( 'nd/v1', '/ads/campaigns/(?P<id>\d+)', array( $this, 'destroy' ), $permission, $idArg );
	}

	public function index(): WP_REST_Response {
		$campaigns = array_map(
			function ( \NDAds\Domain\Campaign $campaign ): array {
				return $this->serialize( $campaign );
			},
			$this->campaigns->all()
		);

		return $this->success( array( 'data' => $campaigns ) );
	}

	public function store( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$fields = $this->extractFields( $request );

		if ( $fields instanceof WP_Error ) {
			return $fields;
		}

		$campaign = $this->campaigns->create( ...$fields );

		return $this->success( array( 'data' => $this->serialize( $campaign ) ), 201 );
	}

	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (int) $request->get_param( 'id' );

		if ( $this->campaigns->find( $id ) === null ) {
			return $this->error( 'nd_ads_campaign_not_found', __( 'Campaña no encontrada.', 'nd-ads' ), 404 );
		}

		$fields = $this->extractFields( $request );

		if ( $fields instanceof WP_Error ) {
			return $fields;
		}

		$this->campaigns->update( $id, ...$fields );

		/** @var \NDAds\Domain\Campaign $updated */
		$updated = $this->campaigns->find( $id );

		return $this->success( array( 'data' => $this->serialize( $updated ) ) );
	}

	public function toggleActive( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (int) $request->get_param( 'id' );

		if ( $this->campaigns->find( $id ) === null ) {
			return $this->error( 'nd_ads_campaign_not_found', __( 'Campaña no encontrada.', 'nd-ads' ), 404 );
		}

		$this->campaigns->setActive( $id, (bool) $request->get_param( 'active' ) );

		return $this->success( array(), 204 );
	}

	public function destroy( WP_REST_Request $request ): WP_REST_Response {
		$this->campaigns->delete( (int) $request->get_param( 'id' ) );

		return $this->success( array(), 204 );
	}

	/**
	 * @return WP_Error|array{0: string, 1: string, 2: CampaignType, 3: bool, 4: int, 5: list<string>, 6: list<string>, 7: array<string, mixed>, 8: string|null, 9: string|null}
	 */
	private function extractFields( WP_REST_Request $request ): WP_Error|array {
		$name       = trim( (string) $request->get_param( 'name' ) );
		$advertiser = trim( (string) $request->get_param( 'advertiser' ) );

		if ( $name === '' || $advertiser === '' ) {
			return $this->error( 'nd_ads_missing_fields', __( 'Nombre y anunciante son obligatorios.', 'nd-ads' ), 422 );
		}

		$typeValue = (string) $request->get_param( 'type' );

		try {
			$type = CampaignType::from( $typeValue );
		} catch ( \ValueError ) {
			return $this->error( 'nd_ads_invalid_type', __( 'Tipo de campaña no válido.', 'nd-ads' ), 422 );
		}

		$zones         = $this->stringList( $request->get_param( 'zones' ) );
		$categorySlugs = $this->stringList( $request->get_param( 'category_slugs' ) );
		$creativeParam = $request->get_param( 'creative' );
		$creative      = is_array( $creativeParam ) ? $creativeParam : array();
		$startsAt      = $request->get_param( 'starts_at' );
		$endsAt        = $request->get_param( 'ends_at' );

		return array(
			$name,
			$advertiser,
			$type,
			(bool) $request->get_param( 'active' ),
			(int) ( $request->get_param( 'priority' ) ?? 0 ),
			$zones,
			$categorySlugs,
			$creative,
			is_string( $startsAt ) && $startsAt !== '' ? $startsAt : null,
			is_string( $endsAt ) && $endsAt !== '' ? $endsAt : null,
		);
	}

	/**
	 * @return list<string>
	 */
	private function stringList( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values( array_filter( $value, 'is_string' ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function serialize( \NDAds\Domain\Campaign $campaign ): array {
		return array(
			'id'             => $campaign->id,
			'name'           => $campaign->name,
			'advertiser'     => $campaign->advertiser,
			'type'           => $campaign->type->value,
			'active'         => $campaign->active,
			'priority'       => $campaign->priority,
			'zones'          => $campaign->zones,
			'category_slugs' => $campaign->categorySlugs,
			'creative'       => $campaign->creative,
			'starts_at'      => $campaign->startsAt,
			'ends_at'        => $campaign->endsAt,
			'stats'          => $this->stats->summaryForCampaign( $campaign->id ),
		);
	}
}
