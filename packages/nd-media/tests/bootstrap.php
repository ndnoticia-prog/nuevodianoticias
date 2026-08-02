<?php

/**
 * Bootstrap de PHPUnit para nd-media.
 *
 * @package NDMedia
 */

declare(strict_types=1);

$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
	fwrite( STDERR, "Faltan las dependencias de Composer. Ejecuta \"composer install\" en packages/nd-media.\n" );
	exit( 1 );
}

require_once $autoloader;
