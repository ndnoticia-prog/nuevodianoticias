<?php

/**
 * Bootstrap de PHPUnit para nd-theme.
 *
 * @package NDTheme
 */

declare(strict_types=1);

$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
	fwrite( STDERR, "Faltan las dependencias de Composer. Ejecuta \"composer install\" en packages/nd-theme.\n" );
	exit( 1 );
}

require_once $autoloader;

if ( ! defined( 'ND_THEME_DIR' ) ) {
	define( 'ND_THEME_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'ND_THEME_URI' ) ) {
	define( 'ND_THEME_URI', 'https://example.test/wp-content/themes/nd-theme' );
}
