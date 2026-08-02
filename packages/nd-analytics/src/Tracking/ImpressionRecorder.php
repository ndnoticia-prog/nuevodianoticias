<?php

declare(strict_types=1);

namespace NDAnalytics\Tracking;

use NDBuilder\Block;
use NDBuilder\Events\BlockRendered;
use NDCore\Database\DatabaseManager;

/**
 * Escucha {@see BlockRendered} (evento interno de nd-builder, no un hook de
 * WordPress) y registra una impresión por cada artículo mostrado en los
 * bloques de portada, base para calcular CTR (impresiones vs. pageviews).
 */
final class ImpressionRecorder {

	private const TABLE = 'analytics_impressions';

	/**
	 * @var list<string>
	 */
	private const TRACKED_BLOCK_TYPES = array( 'hero', 'noticias' );

	public function __construct( private readonly DatabaseManager $db ) {
	}

	public function handle( BlockRendered $event ): void {
		$block = $event->block;

		if ( ! in_array( $block->type, self::TRACKED_BLOCK_TYPES, true ) ) {
			return;
		}

		foreach ( $this->postIdsFromBlock( $block ) as $postId ) {
			$this->record( $postId, $block->type );
		}
	}

	/**
	 * @return list<int>
	 */
	private function postIdsFromBlock( Block $block ): array {
		if ( $block->type === 'hero' ) {
			$postId = $block->attribute( 'post_id' );

			return is_int( $postId ) ? array( $postId ) : array();
		}

		$items = $block->attribute( 'items', array() );

		if ( ! is_array( $items ) ) {
			return array();
		}

		$postIds = array();

		foreach ( $items as $item ) {
			if ( is_array( $item ) && isset( $item['post_id'] ) && is_int( $item['post_id'] ) ) {
				$postIds[] = $item['post_id'];
			}
		}

		return $postIds;
	}

	private function record( int $postId, string $context ): void {
		$this->db->insert(
			$this->db->table( self::TABLE ),
			array(
				'post_id'   => $postId,
				'context'   => $context,
				'viewed_at' => current_time( 'mysql', true ),
			),
			array(
				'post_id'   => '%d',
				'context'   => '%s',
				'viewed_at' => '%s',
			)
		);
	}
}
