<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Hooks;

use Brain\Monkey\Functions;
use Closure;
use Mockery;
use NDCore\Hooks\HookManager;
use NDCore\Tests\BrainMonkeyTestCase;

final class HookManagerTest extends BrainMonkeyTestCase {

	public function test_add_action_registers_with_wordpress_and_remembers_handle(): void {
		$manager  = new HookManager();
		$callback = static function (): void {
		};

		Functions\expect( 'add_action' )
			->once()
			->with( 'nd_core/booted', Mockery::type( Closure::class ), 10, 1 )
			->andReturn( true );

		$handle = $manager->addAction( 'nd_core/booted', $callback );

		self::assertSame( 'action', $handle->type );
		self::assertSame( 'nd_core/booted', $handle->hookName );
		self::assertCount( 1, $manager->registered() );
	}

	public function test_add_filter_registers_with_wordpress(): void {
		$manager  = new HookManager();
		$callback = static fn ( array $value ): array => $value;

		Functions\expect( 'add_filter' )
			->once()
			->with( 'nd_core/providers', Mockery::type( Closure::class ), 20, 1 )
			->andReturn( true );

		$handle = $manager->addFilter( 'nd_core/providers', $callback, 20 );

		self::assertSame( 'filter', $handle->type );
	}

	public function test_do_action_and_apply_filters_delegate_to_wordpress(): void {
		$manager = new HookManager();

		Functions\expect( 'do_action' )->once()->with( 'nd_core/booted', 'payload' );
		$manager->doAction( 'nd_core/booted', 'payload' );

		Functions\expect( 'apply_filters' )->once()->with( 'nd_core/providers', array( 'a' ), 'extra' )->andReturn( array( 'a', 'b' ) );
		$result = $manager->applyFilters( 'nd_core/providers', array( 'a' ), 'extra' );

		self::assertSame( array( 'a', 'b' ), $result );
	}

	public function test_remove_forgets_the_handle(): void {
		$manager  = new HookManager();
		$callback = static function (): void {
		};

		Functions\expect( 'add_action' )->once()->andReturn( true );
		$handle = $manager->addAction( 'nd_core/booted', $callback );

		Functions\expect( 'remove_action' )
			->once()
			->with( 'nd_core/booted', Mockery::type( Closure::class ), 10 )
			->andReturn( true );

		$removed = $manager->remove( $handle );

		self::assertTrue( $removed );
		self::assertCount( 0, $manager->registered() );
	}
}
