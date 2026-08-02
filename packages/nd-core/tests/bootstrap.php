<?php

/**
 * Bootstrap de PHPUnit para nd-core. Las pruebas son unitarias y no
 * requieren una instalación real de WordPress: las funciones nativas de WP
 * se simulan con Brain Monkey caso por caso en cada test.
 *
 * @package NDCore
 */

declare(strict_types=1);

$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
	fwrite( STDERR, "Faltan las dependencias de Composer. Ejecuta \"composer install\" en packages/nd-core.\n" );
	exit( 1 );
}

require_once $autoloader;
