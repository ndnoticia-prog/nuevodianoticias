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

if (! defined('ABSPATH')) {
    exit;
}

const VERSION = '0.1.0-alpha.1';
const MIN_PHP_VERSION = '8.3';
const MIN_WP_VERSION = '6.5';

define('NDCORE_VERSION', VERSION);
define('NDCORE_PLUGIN_FILE', __FILE__);
define('NDCORE_PLUGIN_DIR', __DIR__ . '/');
define('NDCORE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NDCORE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Verifica requisitos mínimos de entorno antes de cargar nada más.
 * Se ejecuta en admin_init/plugins_loaded para poder desactivarse con seguridad
 * si el entorno no cumple, en lugar de producir un fatal error.
 */
function meetsRequirements(): bool
{
    return version_compare(PHP_VERSION, MIN_PHP_VERSION, '>=')
        && version_compare(get_bloginfo('version'), MIN_WP_VERSION, '>=');
}

function deactivateSelfWithNotice(string $message): void
{
    deactivate_plugins(NDCORE_PLUGIN_BASENAME);

    add_action('admin_notices', static function () use ($message): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html($message)
        );
    });

    if (isset($_GET['activate'])) {
        unset($_GET['activate']);
    }
}

$autoloader = NDCORE_PLUGIN_DIR . 'vendor/autoload.php';

if (! file_exists($autoloader)) {
    add_action('admin_notices', static function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__(
                'ND Core: faltan las dependencias de Composer. Ejecuta "composer install" en el directorio del plugin.',
                'nd-core'
            )
        );
    });

    return;
}

require_once $autoloader;

register_activation_hook(__FILE__, static function (bool $networkWide = false): void {
    if (! meetsRequirements()) {
        deactivateSelfWithNotice(sprintf(
            /* translators: 1: required PHP version, 2: required WordPress version */
            esc_html__('ND Core requiere PHP %1$s+ y WordPress %2$s+.', 'nd-core'),
            MIN_PHP_VERSION,
            MIN_WP_VERSION
        ));

        return;
    }

    (new Activation\Activator())->activate($networkWide);
});

register_deactivation_hook(__FILE__, static function (bool $networkWide = false): void {
    (new Activation\Deactivator())->deactivate($networkWide);
});

add_action('plugins_loaded', static function (): void {
    if (! meetsRequirements()) {
        deactivateSelfWithNotice(sprintf(
            /* translators: 1: required PHP version, 2: required WordPress version */
            esc_html__('ND Core requiere PHP %1$s+ y WordPress %2$s+. El plugin se ha desactivado.', 'nd-core'),
            MIN_PHP_VERSION,
            MIN_WP_VERSION
        ));

        return;
    }

    Application::getInstance()->boot();
}, 0);
