<?php

declare(strict_types=1);

namespace NDSearch\Tests\Integration\Indexing;

use NDCore\Database\DatabaseManager;
use NDSearch\Indexing\SearchIndexer;
use WP_UnitTestCase;

/**
 * Prueba SearchIndexer contra WordPress y MySQL reales: sus dos manejadores
 * de hooks (`handleSaved`/`handleDeleted`, enganchados a `save_post`/
 * `before_delete_post` por SearchServiceProvider) reciben un `WP_Post` real
 * y dependen de `wp_is_post_revision()`/`wp_is_post_autosave()`, y
 * `reindexAll()` usa un `WP_Query` real — nada de esto es simulable de
 * forma fiable con Brain Monkey.
 *
 * No se instancia SearchIndexer directamente en la mayoría de las
 * pruebas: como nd-core está cargado como plugin activo en este
 * bootstrap, `self::factory()->post->create()`/`wp_update_post()`/
 * `wp_delete_post()` ya disparan los hooks reales de
 * SearchServiceProvider — es exactamente el flujo que corre en
 * producción.
 */
final class SearchIndexerTest extends WP_UnitTestCase {

	private function isIndexed( int $postId ): bool {
		$db    = new DatabaseManager();
		$table = $db->table( 'search_index' );

		return $db->selectOne( "SELECT post_id FROM {$table} WHERE post_id = %d", array( $postId ) ) !== null;
	}

	public function test_creating_a_published_post_indexes_it_automatically(): void {
		$post = self::factory()->post->create_and_get(
			array(
				'post_status'  => 'publish',
				'post_title'   => 'Titular indexable',
				'post_content' => 'Contenido del artículo.',
			)
		);

		self::assertTrue( $this->isIndexed( $post->ID ) );
	}

	public function test_creating_a_draft_post_does_not_index_it(): void {
		$post = self::factory()->post->create_and_get( array( 'post_status' => 'draft' ) );

		self::assertFalse( $this->isIndexed( $post->ID ) );
	}

	public function test_unpublishing_a_post_removes_it_from_the_index(): void {
		$post = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		self::assertTrue( $this->isIndexed( $post ) );

		wp_update_post(
			array(
				'ID'          => $post,
				'post_status' => 'draft',
			)
		);

		self::assertFalse( $this->isIndexed( $post ) );
	}

	public function test_deleting_a_post_removes_it_from_the_index(): void {
		$post = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		self::assertTrue( $this->isIndexed( $post ) );

		wp_delete_post( $post, true );

		self::assertFalse( $this->isIndexed( $post ) );
	}

	public function test_updating_a_published_post_does_not_index_its_revision(): void {
		$post = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		wp_update_post(
			array(
				'ID'           => $post,
				'post_content' => 'Contenido revisado.',
			)
		);

		$revisions = wp_get_post_revisions( $post );

		self::assertNotEmpty( $revisions, 'WordPress debería haber creado al menos una revisión al actualizar el post.' );

		foreach ( $revisions as $revision ) {
			self::assertFalse( $this->isIndexed( $revision->ID ) );
		}
	}

	public function test_reindex_all_restores_an_entry_removed_out_of_band(): void {
		$db    = new DatabaseManager();
		$table = $db->table( 'search_index' );

		$post = self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_title'   => 'Artículo desincronizado',
				'post_content' => 'Contenido original.',
			)
		);

		self::assertTrue( $this->isIndexed( $post ) );

		// Simula que el índice se desincronizó de $wpdb (p. ej. una
		// importación que no pasó por los hooks de WordPress).
		$db->delete( $table, array( 'post_id' => $post ) );

		self::assertFalse( $this->isIndexed( $post ) );

		$indexer = nd_app()->make( SearchIndexer::class );
		$indexer->reindexAll();

		self::assertTrue( $this->isIndexed( $post ) );
	}

	public function test_reindex_all_returns_the_number_of_published_posts_indexed(): void {
		self::factory()->post->create( array( 'post_status' => 'publish' ) );
		self::factory()->post->create( array( 'post_status' => 'publish' ) );
		self::factory()->post->create( array( 'post_status' => 'draft' ) );

		$db    = new DatabaseManager();
		$total = $db->selectOne(
			"SELECT COUNT(*) AS total FROM {$db->wpTable( 'posts' )} WHERE post_type = 'post' AND post_status = 'publish'"
		);

		$indexer = nd_app()->make( SearchIndexer::class );

		self::assertSame( (int) $total['total'], $indexer->reindexAll() );
	}
}
