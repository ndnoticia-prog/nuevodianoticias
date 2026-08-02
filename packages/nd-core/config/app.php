<?php

/**
 * Configuración general de la aplicación. Otros paquetes pueden añadir sus
 * propios `ServiceProvider` a través del filtro `nd_core/providers` en lugar
 * de editar este archivo.
 */

declare(strict_types=1);

return [
    'env' => defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production',
    'debug' => defined('WP_DEBUG') && WP_DEBUG,
    'providers' => [],
];
