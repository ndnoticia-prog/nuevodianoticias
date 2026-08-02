<?php

declare(strict_types=1);

namespace NDCore\RestApi\Contracts;

use NDCore\Routing\Router;

/**
 * Implementado por cualquier controlador REST que un `ServiceProvider` deba
 * registrar durante `rest_api_init`.
 */
interface RegistersRoutes {

	public function registerRoutes( Router $router ): void;
}
