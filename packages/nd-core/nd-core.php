<?php

/**
 * Plugin Name:       ND Core
 * Plugin URI:        https://github.com/ndnoticia-prog/nd-platform
 * Description:       Núcleo de ND Platform — contenedor de aplicación, servicios y API para el CMS editorial ND.
 * Version:           0.1.0-alpha.1
 * Requires at least: 6.5
 * Requires PHP:      8.3
 * Author:            ND Platform Engineering
 * Author URI:        https://github.com/ndnoticia-prog
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nd-core
 * Domain Path:       /languages
 *
 * @package NDCore
 */

declare(strict_types=1);

namespace NDCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION         = '0.1.0-alpha.1';
const MIN_PHP_VERSION = '8.3';
const MIN_WP_VERSION  = '6.5';

define( 'NDCORE_VERSION', VERSION );
define( 'NDCORE_PLUGIN_FILE', __FILE__ );
define( 'NDCORE_PLUGIN_DIR', __DIR__ . '/' );
define( 'NDCORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NDCORE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Verifica requisitos mínimos de entorno antes de cargar nada más.
 * Se comprueba tanto en la activación como en `after_setup_theme` para
 * poder desactivarse con seguridad si el entorno no cumple, en lugar de
 * producir un fatal error.
 */
function meetsRequirements(): bool {
	return version_compare( PHP_VERSION, MIN_PHP_VERSION, '>=' )
		&& version_compare( get_bloginfo( 'version' ), MIN_WP_VERSION, '>=' );
}

function deactivateSelfWithNotice( string $message ): void {
	deactivate_plugins( NDCORE_PLUGIN_BASENAME );

	add_action(
		'admin_notices',
		static function () use ( $message ): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( $message )
			);
		}
	);

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo se limpia el query arg para la redirección, no se procesa su valor.
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}

if ( ! class_exists( 'NDCore\\Application' ) ) {
	$autoloader = NDCORE_PLUGIN_DIR . 'vendor/autoload.php';

	if ( ! file_exists( $autoloader ) ) {
		add_action(
			'admin_notices',
			static function (): void {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__(
						'ND Core: faltan las dependencias de Composer. Ejecuta "composer install" en el directorio del plugin.',
						'nd-core'
					)
				);
			}
		);

		return;
	}

	// Evita volver a declarar las clases del plugin si ya las cargó otro
	// autoloader en el mismo proceso PHP (p. ej. el arnés compartido de
	// pruebas de integración en tools/wp-tests/phpunit9, que mapea estos
	// mismos namespaces directamente a src/ por ruta para no cargar el
	// PHPUnit de este vendor/ dentro del proceso de un PHPUnit distinto).
	require_once $autoloader;
}

register_activation_hook(
	__FILE__,
	static function ( bool $networkWide = false ): void {
		if ( ! meetsRequirements() ) {
			deactivateSelfWithNotice(
				sprintf(
				/* translators: 1: required PHP version, 2: required WordPress version */
					esc_html__( 'ND Core requiere PHP %1$s+ y WordPress %2$s+.', 'nd-core' ),
					MIN_PHP_VERSION,
					MIN_WP_VERSION
				)
			);

			return;
		}

		( new Activation\Activator() )->activate( $networkWide );
	}
);

register_deactivation_hook(
	__FILE__,
	static function ( bool $networkWide = false ): void {
		( new Activation\Deactivator() )->deactivate( $networkWide );
	}
);

add_action(
	'after_setup_theme',
	static function (): void {
		if ( ! meetsRequirements() ) {
			deactivateSelfWithNotice(
				sprintf(
				/* translators: 1: required PHP version, 2: required WordPress version */
					esc_html__( 'ND Core requiere PHP %1$s+ y WordPress %2$s+. El plugin se ha desactivado.', 'nd-core' ),
					MIN_PHP_VERSION,
					MIN_WP_VERSION
				)
			);

			return;
		}

		// No se arranca en `plugins_loaded`: el tema activo (p. ej. nd-theme)
		// solo tiene ocasión de añadirse al filtro `nd_core/providers` cuando
		// WordPress carga su functions.php, lo cual ocurre DESPUÉS de
		// `plugins_loaded` (entre `setup_theme` y `after_setup_theme`, ver
		// wp-settings.php). Arrancar en `plugins_loaded` significaba que
		// Application::resolveProviderClasses() leía ese filtro antes de que
		// el tema hubiera tenido oportunidad de registrarse — el provider del
		// tema nunca se incluía, así que nd-theme jamás encolaba sus propios
		// assets/menús/theme-supports en un sitio real. Prioridad 5 (antes de
		// la 10 por defecto) para que, si algún ServiceProvider engancha su
		// propio callback a `after_setup_theme` durante Application::boot(),
		// WordPress todavía lo recoja en el mismo ciclo de `do_action()` (el
		// comportamiento estándar de WP_Hook desde 4.7 al añadir un callback
		// de prioridad igual o mayor mientras el hook ya se está disparando).
		Application::getInstance()->boot();
	},
	5
);
