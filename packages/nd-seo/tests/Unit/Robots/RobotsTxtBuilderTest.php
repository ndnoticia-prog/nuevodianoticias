<?php

declare(strict_types=1);

namespace NDSeo\Tests\Unit\Robots;

use Brain\Monkey\Functions;
use NDSeo\Robots\RobotsTxtBuilder;
use NDSeo\Tests\BrainMonkeyTestCase;

final class RobotsTxtBuilderTest extends BrainMonkeyTestCase {

	public function test_appends_sitemap_directives_for_public_sites(): void {
		Functions\expect( 'home_url' )->andReturnUsing(
			static fn ( string $path ): string => 'https://example.test' . $path
		);

		$output = ( new RobotsTxtBuilder() )->filter( "User-agent: *\nDisallow: /wp-admin/", true );

		self::assertStringContainsString( 'Sitemap: https://example.test/wp-sitemap.xml', $output );
		self::assertStringContainsString( 'Sitemap: https://example.test/sitemap-news.xml', $output );
		self::assertStringStartsWith( "User-agent: *\nDisallow: /wp-admin/", $output );
	}

	public function test_leaves_output_untouched_for_private_sites(): void {
		$original = "User-agent: *\nDisallow: /";

		self::assertSame( $original, ( new RobotsTxtBuilder() )->filter( $original, false ) );
	}
}
