<?php

declare(strict_types=1);

namespace NDCore\Settings;

use stdClass;

/**
 * Envuelve `wp_options` bajo un prefijo propio (`nd_`), evitando colisiones
 * con otros plugins y centralizando el control de `autoload`.
 */
final class SettingsRepository {

	private const OPTION_PREFIX = 'nd_';

	public function get( string $key, mixed $default = null ): mixed {
		return get_option( self::OPTION_PREFIX . $key, $default );
	}

	public function set( string $key, mixed $value, bool $autoload = true ): bool {
		return update_option( self::OPTION_PREFIX . $key, $value, $autoload );
	}

	public function forget( string $key ): bool {
		return delete_option( self::OPTION_PREFIX . $key );
	}

	public function has( string $key ): bool {
		$sentinel = new stdClass();

		return $this->get( $key, $sentinel ) !== $sentinel;
	}
}
