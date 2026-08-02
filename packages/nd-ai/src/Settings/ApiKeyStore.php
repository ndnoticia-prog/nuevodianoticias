<?php

declare(strict_types=1);

namespace NDAi\Settings;

use NDCore\Security\Encryption;
use NDCore\Settings\SettingsRepository;
use RuntimeException;

/**
 * Claves de API de proveedores de IA cifradas en reposo con
 * NDCore\Security\Encryption: nunca se guardan en texto plano en
 * wp_options.
 */
final class ApiKeyStore {

	public function __construct(
		private readonly SettingsRepository $settings,
		private readonly Encryption $encryption,
	) {
	}

	public function get( string $provider ): string {
		$encrypted = $this->settings->get( $this->settingKey( $provider ) );

		if ( ! is_string( $encrypted ) || $encrypted === '' ) {
			return '';
		}

		try {
			return $this->encryption->decrypt( $encrypted );
		} catch ( RuntimeException ) {
			return '';
		}
	}

	public function set( string $provider, string $apiKey ): bool {
		if ( $apiKey === '' ) {
			return $this->settings->forget( $this->settingKey( $provider ) );
		}

		return $this->settings->set( $this->settingKey( $provider ), $this->encryption->encrypt( $apiKey ), false );
	}

	private function settingKey( string $provider ): string {
		return 'ai_api_key_' . $provider;
	}
}
