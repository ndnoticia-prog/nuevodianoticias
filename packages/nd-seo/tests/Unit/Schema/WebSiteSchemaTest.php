<?php

declare(strict_types=1);

namespace NDSeo\Tests\Unit\Schema;

use Brain\Monkey\Functions;
use NDSeo\Context\SeoContext;
use NDSeo\Schema\WebSiteSchema;
use NDSeo\Tests\BrainMonkeyTestCase;

final class WebSiteSchemaTest extends BrainMonkeyTestCase {

	public function test_does_not_support_singular_pages(): void {
		$context = new SeoContext(
			title: 'Titular',
			description: '',
			canonicalUrl: 'https://example.test/articulo/',
			imageUrl: null,
			type: 'article',
			isSingular: true,
			noindex: false,
		);

		self::assertFalse( ( new WebSiteSchema() )->supports( $context ) );
	}

	public function test_supports_non_singular_pages_and_includes_search_action(): void {
		Functions\expect( 'home_url' )->andReturnUsing(
			static fn ( string $path = '' ): string => 'https://example.test' . $path
		);
		Functions\expect( 'get_bloginfo' )->with( 'name' )->andReturn( 'Nuevo Diario' );

		$context = new SeoContext(
			title: 'Inicio',
			description: '',
			canonicalUrl: 'https://example.test/',
			imageUrl: null,
			type: 'website',
			isSingular: false,
			noindex: false,
		);

		$schema = ( new WebSiteSchema() )->build( $context );

		self::assertTrue( ( new WebSiteSchema() )->supports( $context ) );
		self::assertSame( 'WebSite', $schema['@type'] );
		self::assertSame(
			'https://example.test/?s={search_term_string}',
			$schema['potentialAction']['target']['urlTemplate']
		);
	}
}
