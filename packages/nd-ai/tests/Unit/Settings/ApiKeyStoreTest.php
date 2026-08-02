<?php

declare(strict_types=1);

namespace NDAi\Tests\Unit\Settings;

use Brain\Monkey\Functions;
use NDAi\Settings\ApiKeyStore;
use NDAi\Tests\BrainMonkeyTestCase;
use NDCore\Security\Encryption;
use NDCore\Settings\SettingsRepository;

final class ApiKeyStoreTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		if ( ! extension_loaded( 'sodium' ) ) {
			self::markTestSkipped( 'La extensión "sodium" no está disponible en este entorno.' );
		}
	}

	public function test_round_trips_a_key_through_encryption(): void {
		$storedValue = null;

		Functions\expect( 'update_option' )->once()->andReturnUsing(
			static function ( string $name, mixed $value ) use ( &$storedValue ): bool {
				$storedValue = $value;

				return true;
			}
		);
		Functions\expect( 'get_option' )->once()->andReturnUsing(
			static function ( string $name, mixed $default = false ) use ( &$storedValue ): mixed {
				return $storedValue ?? $default;
			}
		);

		$encryption = new Encryption( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) );
		$store      = new ApiKeyStore( new SettingsRepository(), $encryption );

		$store->set( 'openai', 'sk-super-secreta' );

		self::assertNotSame( 'sk-super-secreta', $storedValue, 'La clave nunca debe guardarse en texto plano.' );
		self::assertSame( 'sk-super-secreta', $store->get( 'openai' ) );
	}

	public function test_get_returns_empty_string_when_nothing_is_stored(): void {
		Functions\expect( 'get_option' )->once()->andReturn( false );

		$encryption = new Encryption( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) );
		$store      = new ApiKeyStore( new SettingsRepository(), $encryption );

		self::assertSame( '', $store->get( 'openai' ) );
	}

	public function test_set_with_empty_string_forgets_the_key(): void {
		Functions\expect( 'delete_option' )->once()->andReturn( true );

		$encryption = new Encryption( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) );
		$store      = new ApiKeyStore( new SettingsRepository(), $encryption );

		self::assertTrue( $store->set( 'openai', '' ) );
	}
}
