<?php

declare(strict_types=1);

namespace NDAds\Tests\Integration\Stats;

use NDAds\Stats\StatsRecorder;
use NDAds\Stats\StatsRepository;
use NDCore\Database\DatabaseManager;
use WP_UnitTestCase;

/**
 * Prueba StatsRecorder y StatsRepository juntos contra un $wpdb/MySQL
 * reales: StatsRepository::summaryForCampaign() agrega con COUNT(*) sobre
 * las filas que StatsRecorder inserta en `ad_events`, y ese agregado es
 * justo el caso no cubrible con Brain Monkey, documentado desde alpha.4.
 *
 * La tabla `ad_events` NO se crea/destruye aquí: ya existe para cuando
 * esta clase arranca (ver el docblock de CampaignRepositoryTest para la
 * explicación completa de por qué).
 */
final class StatsRepositoryTest extends WP_UnitTestCase {

	private function recorder(): StatsRecorder {
		return new StatsRecorder( new DatabaseManager() );
	}

	private function repository(): StatsRepository {
		return new StatsRepository( new DatabaseManager() );
	}

	public function test_summary_for_a_campaign_with_no_events_is_all_zero(): void {
		$summary = $this->repository()->summaryForCampaign( 123456 );

		self::assertSame(
			array(
				'impressions' => 0,
				'clicks'      => 0,
				'ctr'         => 0.0,
			),
			$summary
		);
	}

	public function test_summary_counts_impressions_and_clicks_and_computes_ctr(): void {
		$recorder   = $this->recorder();
		$campaignId = 1;

		$recorder->recordImpression( $campaignId, 'header' );
		$recorder->recordImpression( $campaignId, 'header' );
		$recorder->recordImpression( $campaignId, 'header' );
		$recorder->recordImpression( $campaignId, 'header' );
		$recorder->recordClick( $campaignId, 'header' );

		$summary = $this->repository()->summaryForCampaign( $campaignId );

		self::assertSame( 4, $summary['impressions'] );
		self::assertSame( 1, $summary['clicks'] );
		self::assertSame( 25.0, $summary['ctr'] );
	}

	public function test_summary_only_counts_events_for_the_requested_campaign(): void {
		$recorder = $this->recorder();

		$recorder->recordImpression( 10, 'header' );
		$recorder->recordClick( 10, 'header' );
		$recorder->recordImpression( 20, 'sidebar' );

		$summary = $this->repository()->summaryForCampaign( 10 );

		self::assertSame( 1, $summary['impressions'] );
		self::assertSame( 1, $summary['clicks'] );
	}

	public function test_record_click_defaults_the_zone_to_an_empty_string(): void {
		$db    = new DatabaseManager();
		$table = $db->table( 'ad_events' );

		$this->recorder()->recordClick( 30 );

		$row = $db->selectOne( "SELECT zone FROM {$table} WHERE campaign_id = %d AND event_type = %s", array( 30, 'click' ) );

		self::assertNotNull( $row );
		self::assertSame( '', $row['zone'] );
	}
}
