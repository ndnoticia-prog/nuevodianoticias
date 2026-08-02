<?php

/**
 * Se ejecuta cuando el usuario elimina el plugin desde el admin de WordPress
 * (no en una simple desactivación). WordPress garantiza que WP_UNINSTALL_PLUGIN
 * está definido antes de incluir este archivo.
 *
 * @package NDCore
 */

declare(strict_types=1);

namespace NDCore;

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$autoloader = __DIR__ . '/vendor/autoload.php';

if (! file_exists($autoloader)) {
    return;
}

require_once $autoloader;

(new Activation\Uninstaller())->uninstall();
