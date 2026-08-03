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

	/**
	 * wp_options.option_value es NOT NULL: guardar `null` directamente con
	 * set_transient() se guarda como '' en la base de datos, no como
	 * `null`, así que se pierde la distinción entre "no cacheado" (false)
	 * y "cacheado como null" al leerlo de vuelta. Este centinela evita esa
	 * pérdida de información.
	 */
	private const NULL_SENTINEL = "\0nd_cache_null\0";

	/**
	 * get_transient()/get_option() devuelven el propio `false` tanto para
	 * "no cacheado" como para "cacheado como false" — la misma ambigüedad
	 * que NULL_SENTINEL resuelve para `null`, aplicada aquí a `false` para
	 * que has()/get() puedan distinguir ambos casos de forma consistente.
	 */
	private const FALSE_SENTINEL = "\0nd_cache_false\0";

	public function get( string $key, mixed $default = null ): mixed {
		$value = get_transient( $this->key( $key ) );

		if ( $value === false ) {
			return $default;
		}

		if ( $value === self::NULL_SENTINEL ) {
			return null;
		}

		return $value === self::FALSE_SENTINEL ? false : $value;
	}

	public function put( string $key, mixed $value, int $ttlSeconds ): bool {
		return set_transient( $this->key( $key ), $this->encode( $value ), $ttlSeconds );
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

	private function encode( mixed $value ): mixed {
		if ( $value === null ) {
			return self::NULL_SENTINEL;
		}

		return $value === false ? self::FALSE_SENTINEL : $value;
	}

	private function key( string $key ): string {
		$fullKey = self::PREFIX . $key;

		return strlen( $fullKey ) > self::MAX_KEY_LENGTH
			? self::PREFIX . hash( 'xxh128', $key )
			: $fullKey;
	}
}
