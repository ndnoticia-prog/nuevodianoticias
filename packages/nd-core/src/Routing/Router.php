<?php

declare(strict_types=1);

namespace NDCore\Routing;

/**
 * API fluida que usan los `ServiceProvider` de cada paquete para declarar
 * rutas REST sin llamar directamente a `register_rest_route()`.
 */
final class Router {

	public function __construct( private readonly RouteCollection $routes ) {
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function get( string $namespace, string $path, callable $handler, ?callable $permission = null, array $args = array() ): Route {
		return $this->register( $namespace, $path, 'GET', $handler, $permission, $args );
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function post( string $namespace, string $path, callable $handler, ?callable $permission = null, array $args = array() ): Route {
		return $this->register( $namespace, $path, 'POST', $handler, $permission, $args );
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function put( string $namespace, string $path, callable $handler, ?callable $permission = null, array $args = array() ): Route {
		return $this->register( $namespace, $path, 'PUT', $handler, $permission, $args );
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function patch( string $namespace, string $path, callable $handler, ?callable $permission = null, array $args = array() ): Route {
		return $this->register( $namespace, $path, 'PATCH', $handler, $permission, $args );
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function delete( string $namespace, string $path, callable $handler, ?callable $permission = null, array $args = array() ): Route {
		return $this->register( $namespace, $path, 'DELETE', $handler, $permission, $args );
	}

	/**
	 * @param string|list<string> $methods
	 * @param array<string, mixed> $args
	 */
	private function register(
		string $namespace,
		string $path,
		string|array $methods,
		callable $handler,
		?callable $permission,
		array $args
	): Route {
		$route = new Route( $namespace, $path, $methods, $handler, $permission, $args );

		$this->routes->add( $route );

		return $route;
	}
}
