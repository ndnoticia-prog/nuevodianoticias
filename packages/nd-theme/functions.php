<?php

/**
 * Bootstrap del tema. No contiene lógica propia: únicamente comprueba que
 * el plugin nd-core está activo, carga el autoloader de Composer de este
 * tema y registra NDTheme\Providers\ThemeServiceProvider en la aplicación
 * de nd-core a través del filtro `nd_core/providers`. Toda la lógica real
 * (theme supports, menús, encolado de assets) vive en ese provider.
 *
 * @package NDTheme
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ND_THEME_VERSION', '0.1.0' );
define( 'ND_THEME_DIR', get_template_directory() . '/' );
define( 'ND_THEME_URI', get_template_directory_uri() );

if ( ! class_exists( 'NDCore\\Application' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__(
					'ND Theme requiere que el plugin "ND Core" esté instalado y activo.',
					'nd-theme'
				)
			);
		}
	);

	return;
}

$autoloader = ND_THEME_DIR . 'vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__(
					'ND Theme: faltan las dependencias de Composer. Ejecuta "composer install" en el directorio del tema.',
					'nd-theme'
				)
			);
		}
	);

	return;
}

require_once $autoloader;

add_filter(
	'nd_core/providers',
	static function ( array $providers ): array {
		$providers[] = NDTheme\Providers\ThemeServiceProvider::class;

		return $providers;
	}
);
