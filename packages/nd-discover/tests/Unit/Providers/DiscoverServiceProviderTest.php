<?php

declare(strict_types=1);

namespace NDDiscover\Tests\Unit\Providers;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use NDCore\Container\Container;
use NDCore\Hooks\HookManager;
use NDDiscover\ImageSizes;
use NDDiscover\Providers\DiscoverServiceProvider;
use NDDiscover\Tests\BrainMonkeyTestCase;

final class DiscoverServiceProviderTest extends BrainMonkeyTestCase {

	public function test_boot_registers_the_featured_image_size_on_after_setup_theme(): void {
		$container = new Container();
		$container->instance( HookManager::class, new HookManager() );

		Functions\expect( 'add_image_size' )
			->once()
			->with( ImageSizes::FEATURED, ImageSizes::FEATURED_WIDTH, ImageSizes::FEATURED_HEIGHT, true );

		Actions\expectAdded( 'after_setup_theme' )->once()->whenHappen(
			static function ( callable $callback ): void {
				$callback();
			}
		);

		( new DiscoverServiceProvider( $container ) )->boot();

		// Las expectativas de Brain Monkey/Mockery arriba ya verifican el
		// comportamiento; esto solo evita que PHPUnit marque el test como
		// "risky" por no llamar a self::assert*() explícitamente.
		$this->addToAssertionCount( 1 );
	}
}
