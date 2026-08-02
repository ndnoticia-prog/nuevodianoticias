<?php

declare(strict_types=1);

namespace NDCore\Routing;

final class RouteCollection {

	/**
	 * @var list<Route>
	 */
	private array $routes = array();

	public function add( Route $route ): void {
		$this->routes[] = $route;
	}

	/**
	 * @return list<Route>
	 */
	public function all(): array {
		return $this->routes;
	}

	/**
	 * @return list<Route>
	 */
	public function forNamespace( string $namespace ): array {
		return array_values(
			array_filter(
				$this->routes,
				static fn ( Route $route ): bool => $route->namespace === $namespace
			)
		);
	}
}
