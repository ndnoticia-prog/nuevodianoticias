<?php

declare(strict_types=1);

namespace NDSeo\Tests\Unit\Meta;

use NDSeo\Context\SeoContext;
use NDSeo\Meta\RobotsMetaBuilder;
use PHPUnit\Framework\TestCase;

final class RobotsMetaBuilderTest extends TestCase {

	public function test_indexable_context_allows_large_image_previews(): void {
		$context = new SeoContext(
			title: 'Titular',
			description: 'Descripción',
			canonicalUrl: 'https://example.test/articulo/',
			imageUrl: null,
			type: 'article',
			isSingular: true,
			noindex: false,
		);

		$robots = ( new RobotsMetaBuilder() )->build( $context );

		self::assertSame( 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1', $robots );
	}

	public function test_noindex_context_is_also_nofollow(): void {
		$context = new SeoContext(
			title: 'Resultados de búsqueda',
			description: '',
			canonicalUrl: '',
			imageUrl: null,
			type: 'website',
			isSingular: false,
			noindex: true,
		);

		self::assertSame( 'noindex, nofollow', ( new RobotsMetaBuilder() )->build( $context ) );
	}
}
