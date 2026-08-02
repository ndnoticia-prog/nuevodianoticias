<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Support;

use Brain\Monkey\Functions;
use NDCore\Support\Str;
use NDCore\Tests\BrainMonkeyTestCase;

final class StrTest extends BrainMonkeyTestCase {

	public function test_slug_normalizes_to_lowercase_hyphenated(): void {
		self::assertSame( 'breaking-news', Str::slug( 'Breaking News' ) );
	}

	public function test_slug_uses_wordpress_remove_accents_when_available(): void {
		Functions\when( 'remove_accents' )->justReturn( 'ultima hora' );

		self::assertSame( 'ultima-hora', Str::slug( 'última hora' ) );
	}

	public function test_studly_and_camel(): void {
		self::assertSame( 'BreakingNews', Str::studly( 'breaking_news' ) );
		self::assertSame( 'breakingNews', Str::camel( 'breaking-news' ) );
	}

	public function test_snake(): void {
		self::assertSame( 'breaking_news', Str::snake( 'BreakingNews' ) );
	}

	public function test_limit_appends_ellipsis_when_truncated(): void {
		self::assertSame( 'Hola…', Str::limit( 'Hola mundo', 4 ) );
		self::assertSame( 'Hola', Str::limit( 'Hola', 10 ) );
	}

	public function test_random_generates_requested_length(): void {
		self::assertSame( 12, strlen( Str::random( 12 ) ) );
	}

	public function test_starts_ends_contains(): void {
		self::assertTrue( Str::startsWith( 'nd-platform', 'nd-' ) );
		self::assertTrue( Str::endsWith( 'nd-platform', 'form' ) );
		self::assertTrue( Str::contains( 'nd-platform', 'plat' ) );
		self::assertFalse( Str::contains( 'nd-platform', 'xyz' ) );
	}
}
