<?php

declare(strict_types=1);

namespace NDSeo\Tests\Integration\Breadcrumbs;

use NDSeo\Breadcrumbs\BreadcrumbBuilder;
use WP_UnitTestCase;

/**
 * Prueba BreadcrumbBuilder contra WordPress real: is_singular()/
 * get_the_category()/get_queried_object() dependen del $wp_query global de
 * una petición real ($this->go_to()), no simulable con Brain Monkey.
 *
 * build() solo usa la PRIMERA categoría que devuelve get_the_category()
 * (la "principal" del post) — no recorre la jerarquía de categorías
 * padre/hija para construir el camino completo. Se crea una jerarquía real
 * (padre/hija vía factory) para confirmar justamente eso: la miga de pan
 * muestra la categoría asignada al post, no su ancestro.
 */
final class BreadcrumbBuilderTest extends WP_UnitTestCase {

	private function builder(): BreadcrumbBuilder {
		return new BreadcrumbBuilder();
	}

	public function test_build_for_the_home_page_returns_only_home(): void {
		$this->go_to( home_url( '/' ) );

		$trail = $this->builder()->build();

		self::assertCount( 1, $trail );
		self::assertSame( home_url( '/' ), $trail[0]->url );
	}

	public function test_build_for_a_post_without_an_explicit_category_falls_back_to_the_default_one(): void {
		// wp_insert_post() asigna la categoría "Uncategorized" por defecto a
		// cualquier post sin post_category explícito (ver su código real);
		// no existe un post sin ninguna categoría en un post_type "post".
		$post = self::factory()->post->create_and_get( array( 'post_title' => 'Sin categoría' ) );

		$this->go_to( (string) get_permalink( $post ) );

		$trail = $this->builder()->build();

		self::assertCount( 3, $trail );
		self::assertSame( get_cat_name( (int) get_option( 'default_category' ) ), $trail[1]->label );
		self::assertSame( 'Sin categoría', $trail[2]->label );
		self::assertSame( get_permalink( $post ), $trail[2]->url );
	}

	public function test_build_for_a_post_uses_its_assigned_category_not_the_parent(): void {
		$parent = self::factory()->category->create_and_get( array( 'name' => 'Deportes' ) );
		$child  = self::factory()->category->create_and_get(
			array(
				'name'   => 'Fútbol',
				'parent' => $parent->term_id,
			)
		);

		$post = self::factory()->post->create_and_get(
			array(
				'post_title'    => 'Final de la Champions',
				'post_category' => array( $child->term_id ),
			)
		);

		$this->go_to( (string) get_permalink( $post ) );

		$trail = $this->builder()->build();

		self::assertCount( 3, $trail );
		self::assertSame( 'Fútbol', $trail[1]->label );
		self::assertSame( get_category_link( $child ), $trail[1]->url );
		self::assertSame( 'Final de la Champions', $trail[2]->label );

		// La categoría padre no aparece en la miga de pan: build() no
		// recorre la jerarquía, solo usa la categoría asignada al post.
		self::assertNotContains( 'Deportes', array_column( $trail, 'label' ) );
	}

	public function test_build_for_a_search_results_page(): void {
		$this->go_to( home_url( '/?s=clima' ) );

		$trail = $this->builder()->build();

		self::assertCount( 2, $trail );
		self::assertStringContainsString( 'clima', $trail[1]->label );
	}

	public function test_build_for_a_category_archive(): void {
		$category = self::factory()->category->create_and_get( array( 'name' => 'Economía' ) );
		self::factory()->post->create( array( 'post_category' => array( $category->term_id ) ) );

		$this->go_to( (string) get_category_link( $category ) );

		$trail = $this->builder()->build();

		self::assertCount( 2, $trail );
		self::assertStringContainsString( 'Economía', $trail[1]->label );
	}
}
