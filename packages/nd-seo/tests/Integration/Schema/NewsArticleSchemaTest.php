<?php

declare(strict_types=1);

namespace NDSeo\Tests\Integration\Schema;

use NDSeo\Context\SeoContext;
use NDSeo\Schema\NewsArticleSchema;
use WP_Post;
use WP_UnitTestCase;

/**
 * Prueba NewsArticleSchema contra un WP_Post real: build() lee
 * get_the_title()/get_the_date()/get_the_modified_date()/
 * get_the_author_meta() directamente del post, nada de eso es simulable de
 * forma fiable con Brain Monkey sin reconstruir WordPress entero.
 */
final class NewsArticleSchemaTest extends WP_UnitTestCase {

	private function schema(): NewsArticleSchema {
		return new NewsArticleSchema();
	}

	private function contextFor( WP_Post $post, string $description = 'Una descripción', ?string $image = 'https://example.test/img.jpg' ): SeoContext {
		return new SeoContext(
			title: 'Título SEO',
			description: $description,
			canonicalUrl: (string) get_permalink( $post ),
			imageUrl: $image,
			type: 'article',
			isSingular: true,
			noindex: false,
			post: $post,
		);
	}

	public function test_supports_requires_a_singular_context_with_a_post(): void {
		$post = self::factory()->post->create_and_get();

		$withPost    = new SeoContext( 't', 'd', 'u', null, 'article', true, false, $post );
		$withoutPost = new SeoContext( 't', 'd', 'u', null, 'article', true, false, null );
		$notSingular = new SeoContext( 't', 'd', 'u', null, 'website', false, false, $post );

		self::assertTrue( $this->schema()->supports( $withPost ) );
		self::assertFalse( $this->schema()->supports( $withoutPost ) );
		self::assertFalse( $this->schema()->supports( $notSingular ) );
	}

	public function test_build_reflects_the_real_post_fields(): void {
		$authorId = self::factory()->user->create( array( 'display_name' => 'Ana Redactora' ) );
		$post     = self::factory()->post->create_and_get(
			array(
				'post_title'  => 'Última hora: <b>algo pasó</b>',
				'post_author' => $authorId,
				'post_date'   => '2026-02-01 08:00:00',
			)
		);

		$schema = $this->schema()->build( $this->contextFor( $post ) );

		self::assertSame( 'NewsArticle', $schema['@type'] );
		self::assertSame( get_permalink( $post ) . '#article', $schema['@id'] );
		self::assertSame( 'Última hora: algo pasó', $schema['headline'] );
		self::assertSame( get_the_date( DATE_W3C, $post ), $schema['datePublished'] );
		self::assertSame( get_the_modified_date( DATE_W3C, $post ), $schema['dateModified'] );
		self::assertSame( 'Ana Redactora', $schema['author']['name'] );
		self::assertSame( 'Una descripción', $schema['description'] );
		self::assertSame( array( 'https://example.test/img.jpg' ), $schema['image'] );
	}

	public function test_build_omits_description_and_image_when_empty(): void {
		$post = self::factory()->post->create_and_get();

		$schema = $this->schema()->build( $this->contextFor( $post, '', null ) );

		self::assertArrayNotHasKey( 'description', $schema );
		self::assertArrayNotHasKey( 'image', $schema );
	}
}
