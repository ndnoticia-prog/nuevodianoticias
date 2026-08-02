<?php

declare(strict_types=1);

namespace NDAds\Repository;

use NDAds\Domain\Campaign;
use NDAds\Domain\CampaignType;
use NDCore\Database\DatabaseManager;

/**
 * Persistencia de campañas. Los campos `zones`/`category_slugs`/`creative`
 * se guardan como JSON: son datos heterogéneos por tipo de campaña
 * (AdSense/GAM/HTML/imagen/video/patrocinado) y filtrar campañas activas
 * por zona/categoría en PHP (tras traer solo las activas) es más simple y
 * suficientemente eficiente para el volumen esperado de campañas.
 */
final class CampaignRepository {

	private const TABLE = 'ad_campaigns';

	public function __construct( private readonly DatabaseManager $db ) {
	}

	/**
	 * @param list<string> $zones
	 * @param list<string> $categorySlugs
	 * @param array<string, mixed> $creative
	 */
	public function create(
		string $name,
		string $advertiser,
		CampaignType $type,
		bool $active,
		int $priority,
		array $zones,
		array $categorySlugs,
		array $creative,
		?string $startsAt,
		?string $endsAt
	): Campaign {
		$id = $this->db->insert(
			$this->db->table( self::TABLE ),
			array(
				'name'           => $name,
				'advertiser'     => $advertiser,
				'type'           => $type->value,
				'active'         => $active ? 1 : 0,
				'priority'       => $priority,
				'zones'          => (string) wp_json_encode( array_values( $zones ) ),
				'category_slugs' => (string) wp_json_encode( array_values( $categorySlugs ) ),
				'creative'       => (string) wp_json_encode( $creative ),
				'starts_at'      => $startsAt,
				'ends_at'        => $endsAt,
				'created_at'     => current_time( 'mysql', true ),
			),
			array(
				'name'           => '%s',
				'advertiser'     => '%s',
				'type'           => '%s',
				'active'         => '%d',
				'priority'       => '%d',
				'zones'          => '%s',
				'category_slugs' => '%s',
				'creative'       => '%s',
				'starts_at'      => '%s',
				'ends_at'        => '%s',
				'created_at'     => '%s',
			)
		);

		return new Campaign( $id, $name, $advertiser, $type, $active, $priority, $zones, $categorySlugs, $creative, $startsAt, $endsAt );
	}

	public function find( int $id ): ?Campaign {
		$table = $this->db->table( self::TABLE );
		$row   = $this->db->selectOne( "SELECT * FROM {$table} WHERE id = %d", array( $id ) );

		return $row !== null ? $this->hydrate( $row ) : null;
	}

	/**
	 * @return list<Campaign>
	 */
	public function active(): array {
		$table = $this->db->table( self::TABLE );
		$rows  = $this->db->select( "SELECT * FROM {$table} WHERE active = 1 ORDER BY priority DESC" );

		return array_map( $this->hydrate( ... ), $rows );
	}

	public function setActive( int $id, bool $active ): bool {
		return (bool) $this->db->update(
			$this->db->table( self::TABLE ),
			array( 'active' => $active ? 1 : 0 ),
			array( 'id' => $id )
		);
	}

	public function delete( int $id ): bool {
		return (bool) $this->db->delete( $this->db->table( self::TABLE ), array( 'id' => $id ) );
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function hydrate( array $row ): Campaign {
		/** @var list<string> $zones */
		$zones = (array) json_decode( (string) $row['zones'], true );

		/** @var list<string> $categorySlugs */
		$categorySlugs = (array) json_decode( (string) $row['category_slugs'], true );

		/** @var array<string, mixed> $creative */
		$creative = (array) json_decode( (string) $row['creative'], true );

		return new Campaign(
			id: (int) $row['id'],
			name: (string) $row['name'],
			advertiser: (string) $row['advertiser'],
			type: CampaignType::from( (string) $row['type'] ),
			active: ( (int) $row['active'] ) === 1,
			priority: (int) $row['priority'],
			zones: $zones,
			categorySlugs: $categorySlugs,
			creative: $creative,
			startsAt: $row['starts_at'] !== null ? (string) $row['starts_at'] : null,
			endsAt: $row['ends_at'] !== null ? (string) $row['ends_at'] : null,
		);
	}
}
