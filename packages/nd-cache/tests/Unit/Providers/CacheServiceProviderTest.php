<?php

declare(strict_types=1);

namespace NDCache\Tests\Unit\Providers;

use NDCache\Invalidation\CacheInvalidator;
use NDCache\PageCache\PageCacheMiddleware;
use NDCache\PageCache\PageCacheStore;
use NDCache\Providers\CacheServiceProvider;
use NDCore\Config\Config;
use NDCore\Container\Container;
use PHPUnit\Framework\TestCase;

final class CacheServiceProviderTest extends TestCase {

	public function test_register_binds_all_services_and_loads_config(): void {
		$container = new Container();
		$config    = new Config();
		$container->instance( Config::class, $config );

		( new CacheServiceProvider( $container ) )->register();

		self::assertInstanceOf( PageCacheStore::class, $container->make( PageCacheStore::class ) );
		self::assertInstanceOf( PageCacheMiddleware::class, $container->make( PageCacheMiddleware::class ) );
		self::assertInstanceOf( CacheInvalidator::class, $container->make( CacheInvalidator::class ) );
		self::assertTrue( $config->get( 'cache.page_cache.enabled' ) );
	}
}
