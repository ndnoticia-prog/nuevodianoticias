<?php

declare(strict_types=1);

namespace NDCore\Cache\Drivers;

use NDCore\Cache\Contracts\CacheDriver;

/**
 * Driver de caché basado en transients de WordPress. Es el driver por
 * defecto cuando no hay un object cache persistente (Redis/Memcached)
 * configurado en el entorno.
 */
final class TransientCacheDriver implements CacheDriver {

	private const PREFIX         = 'nd_';
	private const MAX_KEY_LENGTH = 172;

	public function get( string $key, mixed $default = null ): mixed {
		$value = get_transient( $this->key( $key ) );

		return $value === false ? $default : $value;
	}

	public function put( string $key, mixed $value, int $ttlSeconds ): bool {
		return set_transient( $this->key( $key ), $value, $ttlSeconds );
	}

	public function forget( string $key ): bool {
		return delete_transient( $this->key( $key ) );
	}

	public function flush(): bool {
		global $wpdb;

		$likeTransient = $wpdb->esc_like( '_transient_' . self::PREFIX ) . '%';
		$likeTimeout   = $wpdb->esc_like( '_transient_timeout_' . self::PREFIX ) . '%';

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$likeTransient,
				$likeTimeout
			)
		) !== false;
	}

	public function has( string $key ): bool {
		return get_transient( $this->key( $key ) ) !== false;
	}

	private function key( string $key ): string {
		$fullKey = self::PREFIX . $key;

		return strlen( $fullKey ) > self::MAX_KEY_LENGTH
			? self::PREFIX . hash( 'xxh128', $key )
			: $fullKey;
	}
}
