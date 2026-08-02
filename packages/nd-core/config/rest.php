<?php

declare(strict_types=1);

use NDCore\RestApi\Controllers\SystemController;

return [
    /*
     * Controladores REST que implementan NDCore\RestApi\Contracts\RegistersRoutes.
     * Cada paquete añade los suyos a través del filtro `nd_core/rest_controllers`.
     */
    'controllers' => [
        SystemController::class,
    ],
];
