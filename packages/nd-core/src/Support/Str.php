<?php

declare(strict_types=1);

namespace NDCore\Support;

final class Str {

	private function __construct() {
	}

	public static function slug( string $value, string $separator = '-' ): string {
		$value = self::asciiTransliterate( $value );
		$value = strtolower( $value );
		$value = (string) preg_replace( '/[^a-z0-9]+/', $separator, $value );

		return trim( $value, $separator );
	}

	public static function camel( string $value ): string {
		$studly = self::studly( $value );

		return lcfirst( $studly );
	}

	public static function studly( string $value ): string {
		$value = str_replace( array( '-', '_' ), ' ', $value );
		$value = ucwords( $value );

		return str_replace( ' ', '', $value );
	}

	public static function snake( string $value, string $delimiter = '_' ): string {
		if ( ! ctype_lower( $value ) ) {
			$value = (string) preg_replace( '/\s+/u', '', ucwords( $value ) );
			$value = (string) preg_replace( '/(.)(?=[A-Z])/u', '$1' . $delimiter, $value );
		}

		return strtolower( $value );
	}

	public static function limit( string $value, int $limit = 100, string $end = '…' ): string {
		if ( mb_strwidth( $value, 'UTF-8' ) <= $limit ) {
			return $value;
		}

		return rtrim( mb_strimwidth( $value, 0, $limit, '', 'UTF-8' ) ) . $end;
	}

	public static function random( int $length = 16 ): string {
		$byteCount = max( 1, (int) ceil( $length / 2 ) );

		return substr( bin2hex( random_bytes( $byteCount ) ), 0, $length );
	}

	public static function startsWith( string $haystack, string $needle ): bool {
		return $needle !== '' && str_starts_with( $haystack, $needle );
	}

	public static function endsWith( string $haystack, string $needle ): bool {
		return $needle !== '' && str_ends_with( $haystack, $needle );
	}

	public static function contains( string $haystack, string $needle ): bool {
		return $needle !== '' && str_contains( $haystack, $needle );
	}

	private static function asciiTransliterate( string $value ): string {
		if ( function_exists( 'remove_accents' ) ) {
			return remove_accents( $value );
		}

		if ( ! function_exists( 'iconv' ) ) {
			return $value;
		}

		$transliterated = iconv( 'UTF-8', 'ASCII//TRANSLIT', $value );

		return $transliterated !== false ? $transliterated : $value;
	}
}
