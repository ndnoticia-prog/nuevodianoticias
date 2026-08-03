<?php

declare(strict_types=1);

namespace NDAds\Tests\Integration\Repository;

use NDAds\Domain\Campaign;
use NDAds\Domain\CampaignType;
use NDAds\Repository\CampaignRepository;
use NDCore\Database\DatabaseManager;
use WP_UnitTestCase;

/**
 * Prueba CampaignRepository contra un $wpdb/MySQL reales: los campos
 * `zones`/`category_slugs`/`creative` se serializan a JSON en la tabla y
 * es exactamente ese round-trip serialización/deserialización lo que
 * Brain Monkey no puede simular, documentado como no cubrible desde
 * alpha.4.
 *
 * La tabla `ad_campaigns` NO se crea/destruye aquí: ya existe para cuando
 * esta clase arranca, creada automáticamente por
 * NDCore\Providers\CoreServiceProvider::maybeRunUpgrade() en `init` (ver
 * el docblock de SearchIndexRepositoryTest en nd-search para la
 * explicación completa de por qué gestionar su ciclo de vida aquí
 * rompería esa invariante).
 */
final class CampaignRepositoryTest extends WP_UnitTestCase {

	private function repository(): CampaignRepository {
		return new CampaignRepository( new DatabaseManager() );
	}

	public function test_create_and_find_round_trip_all_fields(): void {
		$repository = $this->repository();

		$created = $repository->create(
			'Campaña de verano',
			'Anunciante S.A.',
			CampaignType::Image,
			true,
			25,
			array( 'header', 'sidebar' ),
			array( 'deportes', 'economia' ),
			array(
				'image_url' => 'https://example.com/ad.png',
				'link_url'  => 'https://example.com',
			),
			'2026-06-01 00:00:00',
			'2026-06-30 23:59:59'
		);

		self::assertGreaterThan( 0, $created->id );

		$found = $repository->find( $created->id );

		self::assertNotNull( $found );
		self::assertSame( 'Campaña de verano', $found->name );
		self::assertSame( 'Anunciante S.A.', $found->advertiser );
		self::assertSame( CampaignType::Image, $found->type );
		self::assertTrue( $found->active );
		self::assertSame( 25, $found->priority );
		self::assertSame( array( 'header', 'sidebar' ), $found->zones );
		self::assertSame( array( 'deportes', 'economia' ), $found->categorySlugs );
		self::assertSame(
			array(
				'image_url' => 'https://example.com/ad.png',
				'link_url'  => 'https://example.com',
			),
			$found->creative
		);
		self::assertSame( '2026-06-01 00:00:00', $found->startsAt );
		self::assertSame( '2026-06-30 23:59:59', $found->endsAt );
	}

	public function test_find_returns_null_for_a_missing_id(): void {
		self::assertNull( $this->repository()->find( 999999 ) );
	}

	public function test_active_only_returns_campaigns_with_the_active_flag_set(): void {
		$repository = $this->repository();

		$active   = $this->createCampaign( $repository, 'Activa', true, 10 );
		$inactive = $this->createCampaign( $repository, 'Inactiva', false, 20 );

		$ids = array_map( static fn ( $campaign ) => $campaign->id, $repository->active() );

		self::assertContains( $active->id, $ids );
		self::assertNotContains( $inactive->id, $ids );
	}

	public function test_active_orders_by_priority_descending(): void {
		$repository = $this->repository();

		$low  = $this->createCampaign( $repository, 'Prioridad baja', true, 1 );
		$high = $this->createCampaign( $repository, 'Prioridad alta', true, 99 );

		$ids       = array_map( static fn ( $campaign ) => $campaign->id, $repository->active() );
		$lowIndex  = array_search( $low->id, $ids, true );
		$highIndex = array_search( $high->id, $ids, true );

		self::assertLessThan( $lowIndex, $highIndex );
	}

	public function test_all_returns_active_and_inactive_campaigns(): void {
		$repository = $this->repository();

		$active   = $this->createCampaign( $repository, 'Activa', true, 10 );
		$inactive = $this->createCampaign( $repository, 'Inactiva', false, 20 );

		$ids = array_map( static fn ( $campaign ) => $campaign->id, $repository->all() );

		self::assertContains( $active->id, $ids );
		self::assertContains( $inactive->id, $ids );
	}

	public function test_update_changes_only_the_targeted_row(): void {
		$repository = $this->repository();

		$target = $this->createCampaign( $repository, 'Original', true, 10 );
		$other  = $this->createCampaign( $repository, 'Sin tocar', true, 10 );

		$result = $repository->update(
			$target->id,
			'Actualizada',
			'Nuevo anunciante',
			CampaignType::Video,
			false,
			50,
			array( 'in-article' ),
			array(),
			array( 'video_url' => 'https://example.com/video.mp4' ),
			null,
			null
		);

		self::assertTrue( $result );

		$updated   = $repository->find( $target->id );
		$untouched = $repository->find( $other->id );

		self::assertNotNull( $updated );
		self::assertSame( 'Actualizada', $updated->name );
		self::assertSame( 'Nuevo anunciante', $updated->advertiser );
		self::assertSame( CampaignType::Video, $updated->type );
		self::assertFalse( $updated->active );
		self::assertSame( 50, $updated->priority );
		self::assertSame( array( 'in-article' ), $updated->zones );
		self::assertSame( array( 'video_url' => 'https://example.com/video.mp4' ), $updated->creative );
		self::assertNull( $updated->startsAt );
		self::assertNull( $updated->endsAt );

		self::assertNotNull( $untouched );
		self::assertSame( 'Sin tocar', $untouched->name );
	}

	public function test_set_active_toggles_the_active_flag(): void {
		$repository = $this->repository();

		$campaign = $this->createCampaign( $repository, 'Alterna', true, 10 );

		self::assertTrue( $repository->setActive( $campaign->id, false ) );
		self::assertFalse( $repository->find( $campaign->id )->active );

		self::assertTrue( $repository->setActive( $campaign->id, true ) );
		self::assertTrue( $repository->find( $campaign->id )->active );
	}

	public function test_delete_removes_the_row(): void {
		$repository = $this->repository();

		$campaign = $this->createCampaign( $repository, 'Efímera', true, 10 );

		self::assertTrue( $repository->delete( $campaign->id ) );
		self::assertNull( $repository->find( $campaign->id ) );
	}

	private function createCampaign( CampaignRepository $repository, string $name, bool $active, int $priority ): Campaign {
		return $repository->create(
			$name,
			'Anunciante',
			CampaignType::Html,
			$active,
			$priority,
			array( 'header' ),
			array(),
			array( 'html' => '<p>Anuncio</p>' ),
			null,
			null
		);
	}
}
