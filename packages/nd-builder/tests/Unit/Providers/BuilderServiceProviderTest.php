<?php

declare(strict_types=1);

namespace NDBuilder\Tests\Unit\Providers;

use NDBuilder\BlockRegistry;
use NDBuilder\Providers\BuilderServiceProvider;
use NDBuilder\Renderer;
use NDCore\Container\Container;
use PHPUnit\Framework\TestCase;

final class BuilderServiceProviderTest extends TestCase {

	public function test_register_binds_registry_with_default_block_types(): void {
		$container = new Container();
		$provider  = new BuilderServiceProvider( $container );

		$provider->register();

		/** @var BlockRegistry $registry */
		$registry = $container->make( BlockRegistry::class );

		self::assertSame( array( 'hero', 'noticias', 'breaking' ), $registry->registeredTypes() );
	}

	public function test_register_binds_renderer_as_singleton(): void {
		$container = new Container();
		$provider  = new BuilderServiceProvider( $container );

		$provider->register();

		$first  = $container->make( Renderer::class );
		$second = $container->make( Renderer::class );

		self::assertSame( $first, $second );
	}
}
