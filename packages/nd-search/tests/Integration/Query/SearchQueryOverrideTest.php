<?php

declare(strict_types=1);

namespace NDSearch\Tests\Integration\Query;

use NDCore\Database\DatabaseManager;
use WP_UnitTestCase;

/**
 * Prueba SearchQueryOverride contra WordPress y MySQL reales: la
 * sustitución del `LIKE` por defecto de WordPress por el índice FULLTEXT
 * solo se puede observar disparando de verdad los hooks `pre_get_posts`/
 * `posts_search` sobre la consulta PRINCIPAL (`WP_Query::is_main_query()`
 * exige serlo), lo que requiere `$this->go_to()` en vez de instanciar un
 * `WP_Query` suelto — mismo caso no cubrible con Brain Monkey documentado
 * desde alpha.5.
 *
 * Igual que SearchIndexRepositoryTest: las filas insertadas sin COMMIT no
 * son visibles para MATCH/AGAINST, así que cada prueba confirma sus
 * cambios explícitamente y los limpia a mano en tearDown() en vez de
 * depender del ROLLBACK automático de WP_UnitTestCase. Ver el docblock de
 * esa clase para la explicación completa.
 */
final class SearchQueryOverrideTest extends WP_UnitTestCase {

	/**
	 * @var list<int>
	 */
	private array $committedPostIds = array();

	protected function setUp(): void {
		parent::setUp();

		// Mismo motivo que en SearchIndexRepositoryTest: con un índice casi
		// vacío, NATURAL LANGUAGE MODE descarta como stopword cualquier
		// término buscado (aparece en >50% del corpus). Se siembra ruido de
		// fondo directamente en el índice, sin necesidad de posts reales.
		$db    = new DatabaseManager();
		$table = $db->table( 'search_index' );

		for ( $i = 1; $i <= 5; $i++ ) {
			$db->insert(
				$table,
				array(
					'post_id'      => 9000 + $i,
					'title'        => "Ruido de fondo {$i}",
					'content_text' => 'lorem ipsum dolor sit amet consectetur adipiscing elit',
					'updated_at'   => current_time( 'mysql', true ),
				),
				array(
					'post_id'      => '%d',
					'title'        => '%s',
					'content_text' => '%s',
					'updated_at'   => '%s',
				)
			);
		}

		$this->commit();
		array_push( $this->committedPostIds, 9001, 9002, 9003, 9004, 9005 );
	}

	protected function tearDown(): void {
		if ( $this->committedPostIds !== array() ) {
			$db    = new DatabaseManager();
			$table = $db->table( 'search_index' );

			foreach ( $this->committedPostIds as $postId ) {
				$db->delete( $table, array( 'post_id' => $postId ) );
			}

			$this->commit();
		}

		parent::tearDown();
	}

	private function commit(): void {
		global $wpdb;

		$wpdb->query( 'COMMIT' );
	}

	private function trackForCleanup( int ...$postIds ): void {
		array_push( $this->committedPostIds, ...$postIds );
	}

	public function test_a_front_end_search_returns_results_from_the_fulltext_index_via_the_real_hook_chain(): void {
		$relevant  = self::factory()->post->create_and_get(
			array(
				'post_status'  => 'publish',
				'post_title'   => 'Elecciones municipales 2026',
				'post_content' => 'El resultado final se conocerá el domingo por la noche.',
			)
		);
		$unrelated = self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_title'   => 'Receta de cocina',
				'post_content' => 'Ingredientes y pasos para preparar el plato.',
			)
		);

		$this->commit();
		$this->trackForCleanup( $relevant->ID, $unrelated );

		$this->go_to( home_url( '/?s=elecciones' ) );

		self::assertTrue( is_search() );

		global $wp_query;

		$foundIds = wp_list_pluck( $wp_query->posts, 'ID' );

		self::assertContains( $relevant->ID, $foundIds );
		self::assertNotContains( $unrelated, $foundIds );
	}

	public function test_the_default_wordpress_like_search_is_neutralized_so_only_the_index_decides_results(): void {
		$db    = new DatabaseManager();
		$table = $db->table( 'search_index' );

		// Contiene literalmente "insólito" en el título, pero se borra a
		// propósito de search_index para simular una desincronización: si
		// neutralizeDefaultSearchSql() no funcionara, el LIKE nativo de
		// WordPress igual lo encontraría por su post_title/post_content.
		$post = self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_title'   => 'Un caso insólito',
				'post_content' => 'Contenido irrelevante para esta prueba.',
			)
		);

		$db->delete( $table, array( 'post_id' => $post ) );
		$this->commit();

		$this->go_to( home_url( '/?s=insólito' ) );

		global $wp_query;

		self::assertNotContains( $post, wp_list_pluck( $wp_query->posts, 'ID' ) );
	}
}
