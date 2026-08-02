<?php

declare(strict_types=1);

namespace NDDiscover\Providers;

use NDCore\Hooks\HookManager;
use NDCore\Providers\ServiceProvider;
use NDDiscover\ImageSizes;

final class DiscoverServiceProvider extends ServiceProvider {

	/**
	 * No hay nada que enlazar en el contenedor: este paquete solo registra
	 * un hook de WordPress (ver boot()).
	 */
	public function register(): void {
	}

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->make( HookManager::class );

		$hooks->addAction(
			'after_setup_theme',
			static function (): void {
				add_image_size( ImageSizes::FEATURED, ImageSizes::FEATURED_WIDTH, ImageSizes::FEATURED_HEIGHT, true );
			}
		);
	}
}
