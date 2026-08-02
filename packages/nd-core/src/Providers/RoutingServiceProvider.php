<?php

declare(strict_types=1);

namespace NDCore\Providers;

use NDCore\Routing\RouteCollection;
use NDCore\Routing\Router;

final class RoutingServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( RouteCollection::class );
		$this->container->singleton( Router::class );
	}
}
