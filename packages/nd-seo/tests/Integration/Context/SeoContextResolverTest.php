<?php

declare(strict_types=1);

namespace NDSeo\Tests\Integration\Context;

use NDCore\Config\Config;
use NDSeo\Context\SeoContextResolver;
use WP_UnitTestCase;

/**
 * Prueba SeoContextResolver contra WordPress real: resolve() depende de
 * is_singular()/is_404()/is_home()/get_queried_object(), que solo una
 * petición real ($this->go_to(), la ejecuta a través de WP::main()) puebla
 * de forma fiable en el $wp_query global — Brain Monkey solo intercepta
 * funciones sueltas, no simula el enrutamiento completo de WordPress.
 */
final class SeoContextResolverTest extends WP_UnitTestCase {

	private function resolver(): SeoContextResolver {
		return new SeoContextResolver( new Config() );
	}

	public function test_resolve_for_a_published_post_returns_an_article_context(): void {
		$post = self::factory()->post->create_and_get(
			array(
				'post_title'   => 'Título de la noticia',
				'post_excerpt' => 'Un resumen breve de la noticia.',
				'post_status'  => 'publish',
			)
		);

		$this->go_to( (string) get_permalink( $post ) );

		$context = $this->resolver()->resolve();

		self::assertTrue( $context->isSingular );
		self::assertSame( 'article', $context->type );
		self::assertFalse( $context->noindex );
		self::assertSame( $post->ID, $context->post?->ID );
		self::assertSame( get_the_title( $post ) . ' - ' . get_bloginfo( 'name' ), $context->title );
		self::assertSame( 'Un resumen breve de la noticia.', $context->description );
		self::assertSame( get_permalink( $post ), $context->canonicalUrl );
		self::assertNull( $context->imageUrl );
	}

	public function test_resolve_truncates_a_long_description_to_160_characters(): void {
		$post = self::factory()->post->create_and_get(
			array(
				'post_excerpt' => str_repeat( 'palabra ', 40 ),
				'post_status'  => 'publish',
			)
		);

		$this->go_to( (string) get_permalink( $post ) );

		$context = $this->resolver()->resolve();

		self::assertLessThanOrEqual( 160, mb_strlen( $context->description ) );
		self::assertStringEndsWith( '...', $context->description );
	}

	public function test_resolve_for_a_private_post_marks_it_noindex(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$post = self::factory()->post->create_and_get( array( 'post_status' => 'private' ) );

		$this->go_to( (string) get_permalink( $post ) );

		$context = $this->resolver()->resolve();

		self::assertTrue( $context->isSingular );
		self::assertTrue( $context->noindex );
	}

	public function test_resolve_for_the_home_page(): void {
		$this->go_to( home_url( '/' ) );

		$context = $this->resolver()->resolve();

		self::assertFalse( $context->isSingular );
		self::assertSame( 'website', $context->type );
		self::assertFalse( $context->noindex );
		self::assertSame( home_url( '/' ), $context->canonicalUrl );
		self::assertNull( $context->post );
	}

	public function test_resolve_for_a_category_archive(): void {
		$category = self::factory()->category->create_and_get( array( 'name' => 'Deportes' ) );
		self::factory()->post->create( array( 'post_category' => array( $category->term_id ) ) );

		$this->go_to( (string) get_category_link( $category ) );

		$context = $this->resolver()->resolve();

		self::assertFalse( $context->isSingular );
		self::assertSame( 'website', $context->type );
		self::assertFalse( $context->noindex );
		self::assertStringContainsString( 'Deportes', $context->title );
	}

	public function test_resolve_for_a_search_results_page(): void {
		$this->go_to( home_url( '/?s=elecciones' ) );

		$context = $this->resolver()->resolve();

		self::assertFalse( $context->isSingular );
		self::assertSame( 'website', $context->type );
		self::assertTrue( $context->noindex );
		self::assertSame( '', $context->description );
		self::assertStringContainsString( 'elecciones', $context->title );
	}

	public function test_resolve_for_a_404(): void {
		$this->go_to( home_url( '/?p=999999' ) );

		$context = $this->resolver()->resolve();

		self::assertFalse( $context->isSingular );
		self::assertSame( 'website', $context->type );
		self::assertTrue( $context->noindex );
		self::assertSame( '', $context->canonicalUrl );
		self::assertSame( '', $context->description );
	}
}
