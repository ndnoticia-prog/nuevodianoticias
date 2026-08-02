<?php

/**
 * PHPStan no puede descubrir constantes definidas vía define() en un
 * archivo (functions.php) cuando se usan en otro (src/*) sin ejecutarlas
 * primero; este bootstrap solo las declara para el análisis estático, con
 * los mismos valores de marcador de posición que usa tests/bootstrap.php.
 *
 * @see https://phpstan.org/user-guide/discovering-symbols
 */

declare(strict_types=1);

if ( ! defined( 'ND_THEME_DIR' ) ) {
	define( 'ND_THEME_DIR', __DIR__ . '/' );
}

if ( ! defined( 'ND_THEME_URI' ) ) {
	define( 'ND_THEME_URI', 'https://example.test/wp-content/themes/nd-theme' );
}
