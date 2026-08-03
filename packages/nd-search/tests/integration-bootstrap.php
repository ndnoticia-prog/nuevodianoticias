<?php

/**
 * Bootstrap de PHPUnit para las pruebas de integración de nd-search contra
 * una base de datos MySQL real: nd-search es un paquete "librería" sin
 * archivo de plugin propio (se activa por completo dentro de nd-core,
 * ver NDCore\Application::resolveProviderClasses()), así que arrancar
 * nd-core.php basta para que también arranque SearchServiceProvider. Mismo
 * patrón que packages/nd-core/tests/integration-bootstrap.php — ver
 * tools/wp-tests/README.md para la infraestructura compartida.
 *
 * @package NDSearch
 */

declare(strict_types=1);

$packageRoot     = dirname( __DIR__ );
$sharedTestsRoot = dirname( $packageRoot, 2 ) . '/tools/wp-tests/phpunit9';

require_once $sharedTestsRoot . '/vendor/autoload.php';

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $sharedTestsRoot . '/vendor/yoast/phpunit-polyfills' );

$wpTestsDir = getenv( 'WP_TESTS_DIR' );

if ( $wpTestsDir === false || $wpTestsDir === '' ) {
	$wpTestsDir = dirname( $packageRoot, 2 ) . '/tools/wp-tests/wordpress-develop';
}

if ( ! is_readable( $wpTestsDir . '/wp-tests-config.php' ) ) {
	fwrite(
		STDERR,
		"Error: no se encontró wp-tests-config.php en \"$wpTestsDir\".\n" .
		"Configura la variable de entorno WP_TESTS_DIR o revisa tools/wp-tests/README.md.\n"
	);
	exit( 1 );
}

define( 'WP_TESTS_CONFIG_FILE_PATH', $wpTestsDir . '/wp-tests-config.php' );

require $wpTestsDir . '/tests/phpunit/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $packageRoot ): void {
		require dirname( $packageRoot ) . '/nd-core/nd-core.php';
	}
);

require $wpTestsDir . '/tests/phpunit/includes/bootstrap.php';
