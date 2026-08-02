<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Cache\Drivers;

use Brain\Monkey\Functions;
use NDCore\Cache\Drivers\TransientCacheDriver;
use NDCore\Tests\BrainMonkeyTestCase;

final class TransientCacheDriverTest extends BrainMonkeyTestCase {

	/**
	 * wp_options.option_value es NOT NULL, así que set_transient(key, null)
	 * no vuelve a leerse como `null`: WordPress::UpdateChecker se topó con
	 * este bug real (un TypeError en producción) porque su callback de
	 * remember() devuelve `?array` y esperaba recuperar `null` tal cual.
	 */
	public function test_a_cached_null_value_round_trips_as_null_not_as_empty_string(): void {
		$store = array();

		Functions\expect( 'set_transient' )
			->once()
			->andReturnUsing(
				static function ( string $key, mixed $value ) use ( &$store ): bool {
					$store[ $key ] = $value;

					return true;
				}
			);

		Functions\expect( 'get_transient' )
			->once()
			->andReturnUsing(
				static function ( string $key ) use ( &$store ): mixed {
					return $store[ $key ] ?? false;
				}
			);

		$driver = new TransientCacheDriver();

		$driver->put( 'updater/latest_release', null, 21600 );

		self::assertNull( $driver->get( 'updater/latest_release', 'not-cached' ) );
	}

	public function test_an_uncached_key_returns_the_given_default(): void {
		Functions\expect( 'get_transient' )->once()->andReturn( false );

		$driver = new TransientCacheDriver();

		self::assertSame( 'fallback', $driver->get( 'missing-key', 'fallback' ) );
	}

	public function test_a_cached_array_round_trips_unchanged(): void {
		$release = array(
			'version' => '1.2.0',
			'url'     => 'https://example.test',
			'package' => 'https://example.test/nd-core.zip',
		);

		Functions\expect( 'get_transient' )->once()->andReturn( $release );

		$driver = new TransientCacheDriver();

		self::assertSame( $release, $driver->get( 'updater/latest_release' ) );
	}
}
