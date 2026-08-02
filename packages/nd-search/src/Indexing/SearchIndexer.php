<?php

declare(strict_types=1);

namespace NDSearch\Indexing;

use NDSearch\Repository\SearchIndexRepository;
use WP_Post;
use WP_Query;

final class SearchIndexer {

	public function __construct( private readonly SearchIndexRepository $repository ) {
	}

	public function handleSaved( int $postId, WP_Post $post ): void {
		if ( wp_is_post_revision( $postId ) !== false || wp_is_post_autosave( $postId ) !== false ) {
			return;
		}

		if ( $post->post_type !== 'post' || $post->post_status !== 'publish' ) {
			$this->repository->delete( $postId );

			return;
		}

		$contentText = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );

		$this->repository->upsert( $postId, get_the_title( $post ), $contentText );
	}

	public function handleDeleted( int $postId ): void {
		$this->repository->delete( $postId );
	}

	/**
	 * Reconstruye el índice completo desde cero: útil tras importar
	 * contenido, cambiar el criterio de indexado, o si el índice se
	 * desincroniza por cualquier motivo. Se ejecuta bajo demanda desde el
	 * panel de admin, nunca automáticamente (podría ser una operación
	 * costosa en sitios con muchos artículos).
	 */
	public function reindexAll(): int {
		$query = new WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);

		$count = 0;

		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$this->handleSaved( $post->ID, $post );
				++$count;
			}
		}

		return $count;
	}
}
