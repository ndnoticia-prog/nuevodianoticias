<?php

declare(strict_types=1);

namespace NDTheme\Tests\Integration\Content;

use NDTheme\Content\HomeContentProvider;
use WP_UnitTestCase;

/**
 * Prueba HomeContentProvider contra WordPress real: traduce un WP_Query
 * real (orden por fecha, category__in) a instancias de NDBuilder\Block, algo
 * que Brain Monkey no puede simular de forma fiable (documentado como
 * pendiente desde alpha.2, ver CHANGELOG/ROADMAP).
 *
 * Un detalle del código real que estas pruebas explotan: ni heroBlock() ni
 * noticiasBlock() filtran por categoría — solo noticiasBlock() excluye el ID
 * del post elegido como hero (post__not_in). Los posts de la categoría
 * "última hora" son perfectamente elegibles también como hero o como
 * elemento de noticias; no hay exclusión mutua entre bloques más allá de esa
 * única exclusión del hero.
 */
final class HomeContentProviderTest extends WP_UnitTestCase {

	private const BREAKING_CATEGORY_SLUG = 'ultima-hora';

	private function provider(): HomeContentProvider {
		return new HomeContentProvider();
	}

	/**
	 * @param int $count
	 * @return list<int>
	 */
	private function createSequentialPosts( int $count, ?int $categoryId = null ): array {
		$ids = array();

		for ( $i = 1; $i <= $count; $i++ ) {
			$args = array(
				'post_date'    => sprintf( '2026-01-%02d 10:00:00', $i ),
				'post_title'   => "Post {$i}",
				'post_excerpt' => "Resumen {$i}",
				'post_status'  => 'publish',
			);

			if ( $categoryId !== null ) {
				$args['post_category'] = array( $categoryId );
			}

			$ids[] = self::factory()->post->create( $args );
		}

		return $ids;
	}

	public function test_without_a_breaking_category_returns_only_hero_and_noticias_in_order(): void {
		// 3 posts con fechas ascendentes: post 3 es el más reciente.
		[$p1, $p2, $p3] = $this->createSequentialPosts( 3 );

		$blocks = $this->provider()->blocksForHomepage();

		self::assertSame( array( 'hero', 'noticias' ), array_map( static fn ( $block ) => $block->type, $blocks ) );

		self::assertSame( $p3, $blocks[0]->attribute( 'post_id' ) );

		$noticiasIds = array_column( $blocks[1]->attribute( 'items' ), 'post_id' );

		self::assertSame( array( $p2, $p1 ), $noticiasIds );
	}

	public function test_with_a_breaking_category_prepends_a_breaking_block(): void {
		$category = self::factory()->category->create( array( 'slug' => self::BREAKING_CATEGORY_SLUG ) );

		// Las dos primeras (más antiguas) van a "última hora"; las cinco
		// siguientes (más recientes) quedan sin categoría especial.
		[$b1, $b2] = $this->createSequentialPosts( 2, $category );
		$regular   = array();

		for ( $i = 3; $i <= 7; $i++ ) {
			$regular[] = self::factory()->post->create(
				array(
					'post_date'   => sprintf( '2026-01-%02d 10:00:00', $i ),
					'post_title'  => "Post {$i}",
					'post_status' => 'publish',
				)
			);
		}

		$blocks = $this->provider()->blocksForHomepage();

		self::assertSame(
			array( 'breaking', 'hero', 'noticias' ),
			array_map( static fn ( $block ) => $block->type, $blocks )
		);

		[$breaking, $hero, $noticias] = $blocks;

		// breaking ordena por fecha descendente dentro de la propia categoría.
		self::assertSame(
			array( 'Post 2', 'Post 1' ),
			array_column( $breaking->attribute( 'items' ), 'title' )
		);
		self::assertArrayNotHasKey( 'post_id', $breaking->attribute( 'items' )[0] );

		// El post más reciente de TODOS (Post 7) es el hero, sin importar categoría.
		self::assertSame( end( $regular ), $hero->attribute( 'post_id' ) );

		// noticias son los 6 restantes (todos menos el hero), en orden descendente
		// por fecha — incluye a los dos posts de "última hora" porque
		// noticiasBlock() no filtra por categoría, solo excluye al hero.
		$expectedNoticiasIds = array_reverse( array( ...array( $b1, $b2 ), ...array_slice( $regular, 0, 4 ) ) );
		self::assertSame( $expectedNoticiasIds, array_column( $noticias->attribute( 'items' ), 'post_id' ) );
	}

	public function test_breaking_block_is_omitted_when_the_category_has_no_posts(): void {
		self::factory()->category->create( array( 'slug' => self::BREAKING_CATEGORY_SLUG ) );
		$this->createSequentialPosts( 2 );

		$blocks = $this->provider()->blocksForHomepage();

		self::assertSame( array( 'hero', 'noticias' ), array_map( static fn ( $block ) => $block->type, $blocks ) );
	}

	public function test_breaking_block_is_omitted_when_the_category_does_not_exist(): void {
		$this->createSequentialPosts( 2 );

		$blocks = $this->provider()->blocksForHomepage();

		self::assertSame( array( 'hero', 'noticias' ), array_map( static fn ( $block ) => $block->type, $blocks ) );
	}

	public function test_post_summary_reports_the_real_post_fields(): void {
		$authorId = self::factory()->user->create( array( 'display_name' => 'Redacción ND' ) );

		self::factory()->post->create(
			array(
				'post_date'    => '2026-01-01 10:00:00',
				'post_title'   => 'Único post',
				'post_excerpt' => 'Un resumen concreto',
				'post_status'  => 'publish',
				'post_author'  => $authorId,
			)
		);

		$blocks = $this->provider()->blocksForHomepage();
		$hero   = $blocks[0];

		self::assertSame( 'Único post', $hero->attribute( 'title' ) );
		self::assertSame( 'Un resumen concreto', $hero->attribute( 'excerpt' ) );
		self::assertSame( 'Redacción ND', $hero->attribute( 'author' ) );
		self::assertNull( $hero->attribute( 'thumbnail' ) );
		self::assertIsString( $hero->attribute( 'permalink' ) );
	}
}
