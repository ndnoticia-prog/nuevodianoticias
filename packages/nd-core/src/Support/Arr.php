<?php

declare(strict_types=1);

namespace NDCore\Support;

use stdClass;

final class Arr {

	private function __construct() {
	}

	/**
	 * @param array<array-key, mixed> $array
	 */
	public static function get( array $array, string $key, mixed $default = null ): mixed {
		if ( array_key_exists( $key, $array ) ) {
			return $array[ $key ];
		}

		$value = $array;

		foreach ( explode( '.', $key ) as $segment ) {
			if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
				return $default;
			}

			$value = $value[ $segment ];
		}

		return $value;
	}

	/**
	 * @param array<array-key, mixed> $array
	 *
	 * @return array<array-key, mixed>
	 */
	public static function set( array $array, string $key, mixed $value ): array {
		$segments    = explode( '.', $key );
		$lastSegment = array_pop( $segments );
		$target      = &$array;

		foreach ( $segments as $segment ) {
			if ( ! isset( $target[ $segment ] ) || ! is_array( $target[ $segment ] ) ) {
				$target[ $segment ] = array();
			}

			$target = &$target[ $segment ];
		}

		$target[ $lastSegment ] = $value;

		return $array;
	}

	/**
	 * @param array<array-key, mixed> $array
	 */
	public static function has( array $array, string $key ): bool {
		$sentinel = new stdClass();

		return self::get( $array, $key, $sentinel ) !== $sentinel;
	}

	/**
	 * @param array<string, mixed> $array
	 * @param list<string> $keys
	 *
	 * @return array<string, mixed>
	 */
	public static function only( array $array, array $keys ): array {
		return array_intersect_key( $array, array_flip( $keys ) );
	}

	/**
	 * @param array<string, mixed> $array
	 * @param list<string> $keys
	 *
	 * @return array<string, mixed>
	 */
	public static function except( array $array, array $keys ): array {
		return array_diff_key( $array, array_flip( $keys ) );
	}

	/**
	 * @param array<array-key, mixed> $array
	 *
	 * @return array<int, mixed>
	 */
	public static function flatten( array $array ): array {
		$result = array();

		array_walk_recursive(
			$array,
			function ( mixed $value ) use ( &$result ): void {
				$result[] = $value;
			}
		);

		return $result;
	}
}
