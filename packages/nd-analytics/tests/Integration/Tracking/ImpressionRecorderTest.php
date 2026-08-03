<?php

declare(strict_types=1);

namespace NDAnalytics\Tests\Integration\Tracking;

use NDAnalytics\Tracking\ImpressionRecorder;
use NDBuilder\Block;
use NDBuilder\Events\BlockRendered;
use NDCore\Database\DatabaseManager;
use WP_UnitTestCase;

/**
 * Prueba ImpressionRecorder contra un $wpdb/MySQL reales: aunque
 * BlockRendered es un objeto de datos simple construible sin WordPress, el
 * propio ImpressionRecorder inserta con DatabaseManager (requiere $wpdb
 * real), el caso documentado como no cubrible con Brain Monkey desde
 * alpha.4.
 */
final class ImpressionRecorderTest extends WP_UnitTestCase {

	private function recorder(): ImpressionRecorder {
		return new ImpressionRecorder( new DatabaseManager() );
	}

	private function impressionsForPost( int $postId ): array {
		$db = new DatabaseManager();

		return $db->select(
			'SELECT * FROM ' . $db->table( 'analytics_impressions' ) . ' WHERE post_id = %d',
			array( $postId )
		);
	}

	public function test_handle_records_an_impression_for_a_hero_block(): void {
		$post  = self::factory()->post->create();
		$block = new Block( 'hero', 'hero-1', array( 'post_id' => $post ) );

		$this->recorder()->handle( new BlockRendered( $block, '<div></div>' ) );

		$rows = $this->impressionsForPost( $post );

		self::assertCount( 1, $rows );
		self::assertSame( 'hero', $rows[0]['context'] );
	}

	public function test_handle_records_an_impression_per_item_in_a_noticias_block(): void {
		$postA = self::factory()->post->create();
		$postB = self::factory()->post->create();

		$block = new Block(
			'noticias',
			'noticias-1',
			array(
				'items' => array(
					array( 'post_id' => $postA ),
					array( 'post_id' => $postB ),
				),
			)
		);

		$this->recorder()->handle( new BlockRendered( $block, '<div></div>' ) );

		self::assertCount( 1, $this->impressionsForPost( $postA ) );
		self::assertCount( 1, $this->impressionsForPost( $postB ) );
	}

	public function test_handle_ignores_untracked_block_types(): void {
		$post  = self::factory()->post->create();
		$block = new Block( 'texto', 'texto-1', array( 'post_id' => $post ) );

		$this->recorder()->handle( new BlockRendered( $block, '<div></div>' ) );

		self::assertSame( array(), $this->impressionsForPost( $post ) );
	}
}
