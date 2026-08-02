<?php

declare(strict_types=1);

namespace NDCore\Providers;

use NDCore\Config\Config;
use NDCore\Hooks\HookManager;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\Controllers\SystemController;
use NDCore\Routing\RouteCollection;
use NDCore\Routing\Router;

/**
 * Traduce las rutas declaradas por cada paquete en llamadas reales a
 * `register_rest_route()`, en el momento correcto del ciclo de vida de
 * WordPress (`rest_api_init`).
 */
final class RestApiServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( SystemController::class );
	}

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->make( HookManager::class );

		$hooks->addAction(
			'rest_api_init',
			function (): void {
				$this->registerControllerRoutes();
				$this->registerRoutesWithWordPress();
			}
		);
	}

	private function registerControllerRoutes(): void {
		/** @var Config $config */
		$config = $this->container->make( Config::class );

		/** @var HookManager $hooks */
		$hooks = $this->container->make( HookManager::class );

		/** @var Router $router */
		$router = $this->container->make( Router::class );

		/** @var list<class-string<RegistersRoutes>> $controllers */
		$controllers = $hooks->applyFilters(
			'nd_core/rest_controllers',
			$config->get( 'rest.controllers', array( SystemController::class ) )
		);

		foreach ( $controllers as $controllerClass ) {
			/** @var RegistersRoutes $controller */
			$controller = $this->container->make( $controllerClass );
			$controller->registerRoutes( $router );
		}
	}

	private function registerRoutesWithWordPress(): void {
		/** @var RouteCollection $routes */
		$routes = $this->container->make( RouteCollection::class );

		foreach ( $routes->all() as $route ) {
			$namespace = $route->namespace;
			$path      = $route->path;

			if ( $namespace === '' || $namespace === '0' || $path === '' || $path === '0' ) {
				continue;
			}

			register_rest_route( $namespace, $path, $route->toRestArgs() );
		}
	}
}
