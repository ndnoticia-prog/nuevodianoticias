<?php

declare(strict_types=1);

namespace NDSearch\Tests\Integration\Repository;

use NDCore\Database\DatabaseManager;
use NDSearch\Repository\SearchIndexRepository;
use WP_UnitTestCase;

/**
 * Prueba SearchIndexRepository contra un MySQL real: el índice FULLTEXT
 * (MATCH ... AGAINST, orden por relevancia) es exactamente el caso que
 * Brain Monkey no puede simular, documentado como no cubrible desde
 * alpha.5.
 *
 * La tabla `search_index` NO se crea/destruye aquí: ya existe para
 * cuando esta clase arranca, creada automáticamente por
 * NDCore\Providers\CoreServiceProvider::maybeRunUpgrade() en `init`
 * (el mismo mecanismo que corre en un sitio real, ver su docblock).
 * Gestionar su ciclo de vida en esta clase rompería esa invariante: un
 * DROP aquí desincroniza la tabla real de lo que `nd_migrations` y la
 * opción `installed_version` creen que ya está instalado, dejando la
 * base de datos de pruebas compartida inconsistente para la siguiente
 * ejecución del proceso.
 *
 * El índice InnoDB FULLTEXT tiene una particularidad que rompe el
 * aislamiento habitual de WP_UnitTestCase (envolver cada prueba en una
 * transacción y revertirla al final): las filas insertadas en una
 * transacción sin COMMIT no son visibles para MATCH() ... AGAINST()
 * hasta que se confirman — a diferencia de un SELECT normal, que sí ve
 * los propios cambios sin confirmar dentro de la misma sesión. Por eso
 * cada prueba que llama a search() confirma explícitamente sus cambios
 * con commit() y este caso de prueba limpia manualmente esas filas en
 * tearDown(), en vez de confiar en el ROLLBACK automático.
 */
final class SearchIndexRepositoryTest extends WP_UnitTestCase {

	/**
	 * @var list<int>
	 */
	private array $committedPostIds = array();

	protected function setUp(): void {
		parent::setUp();

		$repository = $this->repository();

		// MySQL InnoDB descarta en NATURAL LANGUAGE MODE cualquier término
		// presente en más del 50% de las filas indexadas (lo trata como
		// stopword, por diseño: no discrimina resultados). Con una tabla de
		// prueba casi vacía, cualquier término buscado supera ese 50% y
		// MATCH/AGAINST no devuelve nada — no es un fallo de
		// SearchIndexRepository, es el comportamiento real de MySQL con un
		// corpus diminuto. Se siembra "ruido" de fondo con vocabulario no
		// relacionado para que cada prueba refleje un índice de tamaño
		// realista, no ese caso límite.
		for ( $i = 1; $i <= 5; $i++ ) {
			$repository->upsert( 9000 + $i, "Ruido de fondo {$i}", 'lorem ipsum dolor sit amet consectetur adipiscing elit' );
		}

		$this->commit();
		array_push( $this->committedPostIds, 9001, 9002, 9003, 9004, 9005 );
	}

	protected function tearDown(): void {
		if ( $this->committedPostIds !== array() ) {
			$repository = $this->repository();

			foreach ( $this->committedPostIds as $postId ) {
				$repository->delete( $postId );
			}

			$this->commit();
		}

		parent::tearDown();
	}

	private function repository(): SearchIndexRepository {
		return new SearchIndexRepository( new DatabaseManager() );
	}

	private function commit(): void {
		global $wpdb;

		$wpdb->query( 'COMMIT' );
	}

	private function trackForCleanup( int ...$postIds ): void {
		array_push( $this->committedPostIds, ...$postIds );
	}

	public function test_upsert_inserts_a_new_row_that_search_can_find(): void {
		$repository = $this->repository();

		$repository->upsert( 101, 'Elecciones municipales 2026', 'El resultado final se conocerá el domingo por la noche.' );
		$this->commit();
		$this->trackForCleanup( 101 );

		self::assertSame( array( 101 ), $repository->search( 'elecciones' ) );
	}

	public function test_upsert_on_an_existing_post_id_updates_instead_of_duplicating(): void {
		$repository = $this->repository();

		$repository->upsert( 102, 'Título original', 'contenido original' );
		$repository->upsert( 102, 'Título revisado', 'contenido totalmente distinto' );
		$this->commit();
		$this->trackForCleanup( 102 );

		self::assertSame( array(), $repository->search( 'original' ) );
		self::assertSame( array( 102 ), $repository->search( 'revisado' ) );
	}

	public function test_delete_removes_the_post_from_the_index(): void {
		$repository = $this->repository();

		$repository->upsert( 103, 'Artículo temporal', 'contenido temporal' );
		$this->commit();
		$this->trackForCleanup( 103 );

		self::assertSame( array( 103 ), $repository->search( 'temporal' ) );

		$repository->delete( 103 );
		$this->commit();

		self::assertSame( array(), $repository->search( 'temporal' ) );
	}

	public function test_search_orders_by_relevance_not_by_insertion_order(): void {
		$repository = $this->repository();

		// El post 201 menciona "economía" una vez en el cuerpo; el 202 la
		// repite en el título y el cuerpo, así que MySQL debe puntuarlo con
		// mayor relevancia pese a haberse insertado primero.
		$repository->upsert( 201, 'Noticias del día', 'Un breve repaso de la economía nacional.' );
		$repository->upsert( 202, 'Economía: la economía crece', 'La economía sigue en expansión, dicen los analistas.' );
		$this->commit();
		$this->trackForCleanup( 201, 202 );

		self::assertSame( array( 202, 201 ), $repository->search( 'economía' ) );
	}

	public function test_search_respects_the_limit(): void {
		$repository = $this->repository();

		$repository->upsert( 301, 'Deportes A', 'fútbol resultado' );
		$repository->upsert( 302, 'Deportes B', 'fútbol resultado' );
		$repository->upsert( 303, 'Deportes C', 'fútbol resultado' );
		$this->commit();
		$this->trackForCleanup( 301, 302, 303 );

		self::assertCount( 2, $repository->search( 'fútbol', 2 ) );
	}

	public function test_search_with_an_empty_query_returns_no_results(): void {
		self::assertSame( array(), $this->repository()->search( '' ) );
		self::assertSame( array(), $this->repository()->search( '   ' ) );
	}

	public function test_count_reflects_the_number_of_indexed_rows(): void {
		$repository = $this->repository();

		$before = $repository->count();

		$repository->upsert( 401, 'Contador', 'fila para contar' );
		$this->trackForCleanup( 401 );

		self::assertSame( $before + 1, $repository->count() );
	}

	public function test_recent_returns_the_most_recently_updated_rows_first(): void {
		$repository = $this->repository();

		$repository->upsert( 501, 'Primero', 'contenido' );
		sleep( 1 );
		$repository->upsert( 502, 'Segundo', 'contenido' );
		$this->trackForCleanup( 501, 502 );

		$titles = array_column( $repository->recent( 2 ), 'title' );

		self::assertSame( 'Segundo', $titles[0] );
	}
}
