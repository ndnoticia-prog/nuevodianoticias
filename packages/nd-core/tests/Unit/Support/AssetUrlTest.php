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

	/**
	 * forPackage() existe porque for() falla en un entorno de desarrollo
	 * con `repositories` de tipo `path`: los symlinks que crea Composer
	 * hacen que __DIR__, dentro de un paquete hermano como nd-workflow,
	 * resuelva a su ubicación real fuera del árbol de nd-core (aunque en
	 * producción, con archivos copiados de verdad, sí habría funcionado).
	 */
	public function test_for_package_resolves_a_bundled_vendor_package_asset(): void {
		self::assertSame(
			'https://example.test/wp-content/plugins/nd-core/vendor/ndnoticia/nd-workflow/assets/admin/calendar.js',
			AssetUrl::forPackage( 'ndnoticia/nd-workflow', 'assets/admin/calendar.js' )
		);
	}

	public function test_for_package_normalizes_leading_and_trailing_slashes(): void {
		self::assertSame(
			'https://example.test/wp-content/plugins/nd-core/vendor/ndnoticia/nd-workflow/assets/admin/calendar.js',
			AssetUrl::forPackage( '/ndnoticia/nd-workflow/', '/assets/admin/calendar.js' )
		);
	}
}
