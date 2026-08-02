<?php

declare(strict_types=1);

namespace NDCore\Security;

use RuntimeException;
use SodiumException;

/**
 * Cifrado simétrico autenticado (XSalsa20-Poly1305 vía libsodium) usado para
 * almacenar en reposo credenciales de terceros (claves de proveedores de IA,
 * tokens de servicios de anuncios), que nunca deben quedar en texto plano en
 * `wp_options`.
 */
final class Encryption {

	/**
	 * @param string $key Clave binaria de exactamente `SODIUM_CRYPTO_SECRETBOX_KEYBYTES` bytes.
	 */
	public function __construct( private readonly string $key ) {
		if ( ! extension_loaded( 'sodium' ) ) {
			throw new RuntimeException( 'NDCore\Security\Encryption requiere la extensión "sodium", no disponible en este entorno.' );
		}

		if ( strlen( $this->key ) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) {
			throw new RuntimeException( 'La clave de cifrado debe tener exactamente SODIUM_CRYPTO_SECRETBOX_KEYBYTES bytes.' );
		}
	}

	/**
	 * Deriva una clave estable de 32 bytes a partir de los salts únicos de
	 * esta instalación de WordPress (`AUTH_KEY`/`SECURE_AUTH_KEY`), evitando
	 * tener que gestionar una clave de cifrado adicional por separado.
	 */
	public static function fromWordPressSalts(): self {
		$material = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . ( defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '' );

		if ( $material === '' ) {
			throw new RuntimeException(
				'No se encontraron AUTH_KEY/SECURE_AUTH_KEY en wp-config.php para derivar la clave de cifrado.'
			);
		}

		$key = sodium_crypto_generichash( $material, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );

		return new self( $key );
	}

	public function encrypt( string $plaintext ): string {
		$nonce      = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = sodium_crypto_secretbox( $plaintext, $nonce, $this->key );

		return base64_encode( $nonce . $ciphertext );
	}

	public function decrypt( string $encoded ): string {
		$decoded = base64_decode( $encoded, true );

		if ( $decoded === false || strlen( $decoded ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			throw new RuntimeException( 'Payload cifrado inválido: no se pudo decodificar en base64 o es demasiado corto.' );
		}

		$nonce      = substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		if ( strlen( $ciphertext ) < SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
			throw new RuntimeException( 'Payload cifrado inválido: el ciphertext es más corto que el MAC mínimo.' );
		}

		try {
			$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $this->key );
		} catch ( SodiumException $exception ) {
			throw new RuntimeException( 'No se pudo descifrar el valor: clave incorrecta o datos corruptos.', 0, $exception );
		}

		if ( $plaintext === false ) {
			throw new RuntimeException( 'No se pudo descifrar el valor: clave incorrecta o datos corruptos.' );
		}

		return $plaintext;
	}
}
