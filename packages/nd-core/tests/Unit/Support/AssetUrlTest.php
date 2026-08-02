<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Support;

use NDCore\Support\AssetUrl;
use NDCore\Tests\BrainMonkeyTestCase;

final class AssetUrlTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'NDCORE_PLUGIN_DIR' ) ) {
			define( 'NDCORE_PLUGIN_DIR', '/var/www/wp-content/plugins/nd-core/' );
		}

		if ( ! defined( 'NDCORE_PLUGIN_URL' ) ) {
			define( 'NDCORE_PLUGIN_URL', 'https://example.test/wp-content/plugins/nd-core/' );
		}
	}

	public function test_resolves_a_path_inside_the_plugin_directory(): void {
		$path = NDCORE_PLUGIN_DIR . 'assets/admin/calendar.js';

		self::assertSame(
			'https://example.test/wp-content/plugins/nd-core/assets/admin/calendar.js',
			AssetUrl::for( $path )
		);
	}

	public function test_resolves_a_path_inside_a_bundled_vendor_package(): void {
		$path = NDCORE_PLUGIN_DIR . 'vendor/ndnoticia/nd-workflow/assets/admin/calendar.js';

		self::assertSame(
			'https://example.test/wp-content/plugins/nd-core/vendor/ndnoticia/nd-workflow/assets/admin/calendar.js',
			AssetUrl::for( $path )
		);
	}

	public function test_returns_empty_string_for_a_path_outside_the_plugin_directory(): void {
		self::assertSame( '', AssetUrl::for( '/etc/passwd' ) );
	}
}
