<?php

declare(strict_types=1);

namespace NDAds\Tests\Unit\Domain;

use DateTimeImmutable;
use NDAds\Domain\Campaign;
use NDAds\Domain\CampaignType;
use PHPUnit\Framework\TestCase;

final class CampaignTest extends TestCase {

	private function campaign(
		array $zones = array( 'header' ),
		array $categorySlugs = array(),
		?string $startsAt = null,
		?string $endsAt = null
	): Campaign {
		return new Campaign(
			id: 1,
			name: 'Campaña de prueba',
			advertiser: 'Anunciante',
			type: CampaignType::Html,
			active: true,
			priority: 10,
			zones: $zones,
			categorySlugs: $categorySlugs,
			creative: array( 'html' => '<p>Anuncio</p>' ),
			startsAt: $startsAt,
			endsAt: $endsAt,
		);
	}

	public function test_matches_zone(): void {
		$campaign = $this->campaign( array( 'header', 'sidebar' ) );

		self::assertTrue( $campaign->matchesZone( 'header' ) );
		self::assertFalse( $campaign->matchesZone( 'footer' ) );
	}

	public function test_matches_categories_when_no_targeting_configured(): void {
		$campaign = $this->campaign( categorySlugs: array() );

		self::assertTrue( $campaign->matchesCategories( array( 'deportes' ) ) );
		self::assertTrue( $campaign->matchesCategories( array() ) );
	}

	public function test_matches_categories_with_targeting(): void {
		$campaign = $this->campaign( categorySlugs: array( 'deportes', 'economia' ) );

		self::assertTrue( $campaign->matchesCategories( array( 'deportes' ) ) );
		self::assertFalse( $campaign->matchesCategories( array( 'cultura' ) ) );
	}

	public function test_is_scheduled_at_without_dates_is_always_active(): void {
		$campaign = $this->campaign();

		self::assertTrue( $campaign->isScheduledAt( new DateTimeImmutable( '2026-06-01' ) ) );
	}

	public function test_is_scheduled_at_respects_start_and_end_dates(): void {
		$campaign = $this->campaign( startsAt: '2026-06-01 00:00:00', endsAt: '2026-06-30 23:59:59' );

		self::assertFalse( $campaign->isScheduledAt( new DateTimeImmutable( '2026-05-31' ) ) );
		self::assertTrue( $campaign->isScheduledAt( new DateTimeImmutable( '2026-06-15' ) ) );
		self::assertFalse( $campaign->isScheduledAt( new DateTimeImmutable( '2026-07-01' ) ) );
	}
}
