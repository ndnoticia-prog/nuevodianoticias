<?php

/**
 * Funciones globales de conveniencia de ND Platform. Cada una es un atajo
 * fino sobre el contenedor de la aplicación: no contienen lógica propia.
 *
 * @package NDCore
 */

declare(strict_types=1);

use NDCore\Application;
use NDCore\Cache\CacheManager;
use NDCore\Config\Config;
use NDCore\Settings\SettingsRepository;

if ( ! function_exists( 'nd_app' ) ) {
	/**
	 * @template T of object
	 *
	 * @param class-string<T>|null $abstract
	 *
	 * @return ($abstract is null ? Application : T)
	 */
	function nd_app( ?string $abstract = null ): mixed {
		$app = Application::getInstance();

		return $abstract === null ? $app : $app->make( $abstract );
	}
}

if ( ! function_exists( 'nd_config' ) ) {
	function nd_config( string $key, mixed $default = null ): mixed {
		return nd_app( Config::class )->get( $key, $default );
	}
}

if ( ! function_exists( 'nd_cache' ) ) {
	function nd_cache(): CacheManager {
		return nd_app( CacheManager::class );
	}
}

if ( ! function_exists( 'nd_settings' ) ) {
	function nd_settings(): SettingsRepository {
		return nd_app( SettingsRepository::class );
	}
}
