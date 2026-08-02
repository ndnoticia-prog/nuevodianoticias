<?php

declare(strict_types=1);

namespace NDCore\Cache\Drivers;

use NDCore\Cache\Contracts\CacheDriver;

/**
 * Driver de caché basado en el Object Cache de WordPress (`wp_cache_*`).
 * Cuando hay un backend persistente instalado (Redis, Memcached) mediante un
 * drop-in `object-cache.php`, este driver es efectivamente un cliente de esa
 * caché persistente.
 */
final class ObjectCacheDriver implements CacheDriver {

	private const GROUP = 'nd_platform';

	public function get( string $key, mixed $default = null ): mixed {
		$found = false;
		$value = wp_cache_get( $key, self::GROUP, false, $found );

		return $found ? $value : $default;
	}

	public function put( string $key, mixed $value, int $ttlSeconds ): bool {
		return wp_cache_set( $key, $value, self::GROUP, $ttlSeconds );
	}

	public function forget( string $key ): bool {
		return wp_cache_delete( $key, self::GROUP );
	}

	public function flush(): bool {
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			return wp_cache_flush_group( self::GROUP );
		}

		return wp_cache_flush();
	}

	public function has( string $key ): bool {
		$found = false;
		wp_cache_get( $key, self::GROUP, false, $found );

		return $found;
	}
}
