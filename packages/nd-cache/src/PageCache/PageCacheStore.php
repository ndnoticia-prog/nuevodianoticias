<?php

declare(strict_types=1);

namespace NDCache\PageCache;

use NDCore\Cache\CacheManager;
use NDCore\Config\Config;

/**
 * Almacena HTML de página completa reutilizando el CacheManager de nd-core
 * (mismo driver que ya elija el sitio: transient/object-cache/redis) en
 * lugar de implementar su propio backend de almacenamiento.
 */
final class PageCacheStore {

	private const DEFAULT_TTL = 3600;

	public function __construct(
		private readonly CacheManager $cache,
		private readonly Config $config,
	) {
	}

	public function get( string $key ): ?string {
		$value = $this->cache->get( $key );

		return is_string( $value ) ? $value : null;
	}

	public function put( string $key, string $html ): bool {
		$ttl = (int) $this->config->get( 'cache.page_cache.ttl', self::DEFAULT_TTL );

		return $this->cache->put( $key, $html, $ttl );
	}

	public function forget( string $key ): bool {
		return $this->cache->forget( $key );
	}
}
