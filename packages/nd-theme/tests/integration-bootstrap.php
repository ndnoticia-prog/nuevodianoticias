<?php

/**
 * Bootstrap de PHPUnit para las pruebas de integración de nd-theme contra
 * una base de datos MySQL real.
 *
 * A diferencia de nd-core/nd-search/nd-seo, nd-theme es un TEMA de
 * WordPress, no un plugin: su único ServiceProvider
 * (NDTheme\Providers\ThemeServiceProvider) solo se registra cuando
 * WordPress carga functions.php del tema activo, a través del filtro
 * `nd_core/providers` (ver functions.php) — y NDCore\Application::
 * resolveProviderClasses() no lo referencia en absoluto (a diferencia de
 * NDSeo\Providers\SeoServiceProvider, con su propio class_exists() ahí).
 * Verificado leyendo packages/nd-core/src/Application.php antes de escribir
 * este archivo: no hay ningún mecanismo que active nd-theme "como si fuera
 * un plugin", así que replicar el patrón de tests/integration-bootstrap.php
 * de nd-core/nd-search (requerir un archivo principal vía muplugins_loaded)
 * no aplica aquí.
 *
 * Esto no es una limitación: la única clase de nd-theme pendiente de
 * pruebas de integración, {@see \NDTheme\Content\HomeContentProvider}, es
 * PHP puro sin ninguna dependencia del Container/HookManager/
 * ThemeServiceProvider — solo traduce un WP_Query real en instancias de
 * NDBuilder\Block (ver su código). No hace falta cargar nd-core como
 * plugin activo ni simular la activación del tema; basta con el WordPress
 * real del arnés de pruebas.
 *
 * @package NDTheme
 */

declare(strict_types=1);

$packageRoot     = dirname( __DIR__ );
$sharedTestsRoot = dirname( $packageRoot, 2 ) . '/tools/wp-tests/phpunit9';

require_once $sharedTestsRoot . '/vendor/autoload.php';

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $sharedTestsRoot . '/vendor/yoast/phpunit-polyfills' );

// El autoloader compartido de tools/wp-tests/phpunit9 (ver su composer.json)
// mapea los namespaces de los paquetes empaquetados dentro del vendor/ de
// nd-core (NDSeo, NDBuilder, ...) directamente a su src/, pero no NDTheme:
// nd-theme es un tema, no uno de esos paquetes, así que no está en ese
// mapa. Se registra aquí un autoloader PSR-4 mínimo únicamente para este
// namespace, sin tocar el archivo compartido (fuera del alcance de este
// paquete).
spl_autoload_register(
	static function ( string $class ) use ( $packageRoot ): void {
		$prefix = 'NDTheme\\';

		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relativeClass = substr( $class, strlen( $prefix ) );
		$file          = $packageRoot . '/src/' . str_replace( '\\', '/', $relativeClass ) . '.php';

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

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

require $wpTestsDir . '/tests/phpunit/includes/bootstrap.php';
