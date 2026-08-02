<?php

/**
 * PHPStan no puede descubrir constantes definidas vía define() en un
 * archivo (nd-core.php) cuando se usan en un método distinto de aquel donde
 * se comprueba defined() (p. ej. CoreServiceProvider::boot() comprueba
 * defined('NDCORE_VERSION'), pero el uso real está en
 * CoreServiceProvider::maybeRunUpgrade(), un método aparte); este
 * bootstrap solo las declara para el análisis estático.
 *
 * @see https://phpstan.org/user-guide/discovering-symbols
 */

declare(strict_types=1);

if ( ! defined( 'NDCORE_VERSION' ) ) {
	define( 'NDCORE_VERSION', '0.0.0' );
}

if ( ! defined( 'NDCORE_PLUGIN_FILE' ) ) {
	define( 'NDCORE_PLUGIN_FILE', __DIR__ . '/nd-core.php' );
}

if ( ! defined( 'NDCORE_PLUGIN_DIR' ) ) {
	define( 'NDCORE_PLUGIN_DIR', __DIR__ . '/' );
}

if ( ! defined( 'NDCORE_PLUGIN_URL' ) ) {
	define( 'NDCORE_PLUGIN_URL', 'https://example.test/wp-content/plugins/nd-core/' );
}
