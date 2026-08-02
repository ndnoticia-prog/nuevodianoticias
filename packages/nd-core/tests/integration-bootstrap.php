<?php

/**
 * Bootstrap de PHPUnit para las pruebas de integración de nd-core: carga el
 * núcleo real de WordPress (no Brain Monkey) contra una base de datos MySQL
 * de pruebas, siguiendo el flujo documentado en
 * tests/phpunit/includes/bootstrap.php de wordpress-develop para
 * "Plugin/theme integration tests" con una instalación parcial del test
 * suite (WP_TESTS_PHPUNIT_POLYFILLS_PATH + WP_TESTS_CONFIG_FILE_PATH).
 *
 * @package NDCore
 */

declare(strict_types=1);

$packageRoot     = dirname( __DIR__ );
$sharedTestsRoot = dirname( $packageRoot, 2 ) . '/tools/wp-tests/phpunit9';

// El arnés de pruebas de wordpress-develop (WP_UnitTestCase::expectDeprecated())
// todavía llama a PHPUnit\Util\Test::parseTestMethodAnnotations(), eliminado
// en PHPUnit 11 (que usan las pruebas unitarias de este paquete); las
// pruebas de integración corren con un PHPUnit 9 aislado y compartido por
// todos los paquetes en vez de con el PHPUnit 11 propio.
//
// Deliberadamente NO se carga el propio vendor/autoload.php de este
// paquete: su classmap de require-dev incluye PHPUnit 11, y cargarlo en el
// mismo proceso que el binario de PHPUnit 9 produce un choque de clases
// (`PHPUnit\TextUI\TestSuiteMapper` de la versión equivocada). En su lugar,
// el autoloader compartido de tools/wp-tests/phpunit9 mapea directamente
// los namespaces de cada paquete a su propio src/ por ruta — ver
// tools/wp-tests/README.md y el script "test:integration" en composer.json.
require_once $sharedTestsRoot . '/vendor/autoload.php';

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $sharedTestsRoot . '/vendor/yoast/phpunit-polyfills' );

$wpTestsDir = getenv( 'WP_TESTS_DIR' );

if ( $wpTestsDir === false || $wpTestsDir === '' ) {
	// Ubicación por defecto del checkout compartido de wordpress-develop
	// (ver tools/wp-tests/README.md), fuera de cada paquete para no
	// duplicar los ~180MB del checkout 13 veces.
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

/**
 * Carga nd-core como si fuera un plugin activo, en el mismo punto del ciclo
 * de vida (`muplugins_loaded`) donde el propio test suite de WordPress
 * espera que se carguen los plugins bajo prueba.
 */
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__ ) . '/nd-core.php';
	}
);

require $wpTestsDir . '/tests/phpunit/includes/bootstrap.php';
