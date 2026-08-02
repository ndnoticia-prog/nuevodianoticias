<?php

declare(strict_types=1);

namespace NDAds\Stats;

use NDCore\Database\DatabaseManager;

final class StatsRecorder {

	public function __construct( private readonly DatabaseManager $db ) {
	}

	public function recordImpression( int $campaignId, string $zone ): void {
		$this->record( $campaignId, 'impression', $zone );
	}

	public function recordClick( int $campaignId, string $zone = '' ): void {
		$this->record( $campaignId, 'click', $zone );
	}

	private function record( int $campaignId, string $eventType, string $zone ): void {
		$this->db->insert(
			$this->db->table( 'ad_events' ),
			array(
				'campaign_id' => $campaignId,
				'event_type'  => $eventType,
				'zone'        => $zone,
				'created_at'  => current_time( 'mysql', true ),
			),
			array(
				'campaign_id' => '%d',
				'event_type'  => '%s',
				'zone'        => '%s',
				'created_at'  => '%s',
			)
		);
	}
}
