<?php

/**
 * Bootstrap de PHPUnit para nd-analytics.
 *
 * @package NDAnalytics
 */

declare(strict_types=1);

$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
	fwrite( STDERR, "Faltan las dependencias de Composer. Ejecuta \"composer install\" en packages/nd-analytics.\n" );
	exit( 1 );
}

require_once $autoloader;

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
