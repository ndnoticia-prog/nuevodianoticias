<?php

declare(strict_types=1);

namespace NDCache\Tests\Unit\PageCache;

use Brain\Monkey\Functions;
use NDCache\PageCache\PageCacheStore;
use NDCache\Tests\BrainMonkeyTestCase;
use NDCore\Cache\CacheManager;
use NDCore\Config\Config;

final class PageCacheStoreTest extends BrainMonkeyTestCase {

	public function test_put_uses_configured_ttl(): void {
		Functions\expect( 'set_transient' )->once()->with( 'nd_page:abc', '<html></html>', 7200 )->andReturn( true );

		$config = new Config( array( 'cache' => array( 'page_cache' => array( 'ttl' => 7200 ) ) ) );
		$store  = new PageCacheStore( new CacheManager( $config ), $config );

		self::assertTrue( $store->put( 'page:abc', '<html></html>' ) );
	}

	public function test_get_returns_null_when_not_a_string(): void {
		Functions\expect( 'get_transient' )->once()->with( 'nd_page:missing' )->andReturn( false );

		$config = new Config();
		$store  = new PageCacheStore( new CacheManager( $config ), $config );

		self::assertNull( $store->get( 'page:missing' ) );
	}

	public function test_forget_delegates_to_cache_manager(): void {
		Functions\expect( 'delete_transient' )->once()->with( 'nd_page:abc' )->andReturn( true );

		$config = new Config();
		$store  = new PageCacheStore( new CacheManager( $config ), $config );

		self::assertTrue( $store->forget( 'page:abc' ) );
	}
}
