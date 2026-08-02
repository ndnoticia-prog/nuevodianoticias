<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Security;

use NDCore\Security\Encryption;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EncryptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sodium')) {
            self::markTestSkipped('La extensión "sodium" no está disponible en este entorno.');
        }
    }

    public function test_encrypt_then_decrypt_round_trip(): void
    {
        $encryption = new Encryption(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        $ciphertext = $encryption->encrypt('sk-clave-secreta-de-un-proveedor-de-ia');

        self::assertNotSame('sk-clave-secreta-de-un-proveedor-de-ia', $ciphertext);
        self::assertSame('sk-clave-secreta-de-un-proveedor-de-ia', $encryption->decrypt($ciphertext));
    }

    public function test_two_encryptions_of_the_same_value_differ(): void
    {
        $encryption = new Encryption(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        $first = $encryption->encrypt('mismo-valor');
        $second = $encryption->encrypt('mismo-valor');

        self::assertNotSame($first, $second, 'El nonce aleatorio debe producir ciphertexts distintos.');
    }

    public function test_constructor_rejects_key_with_wrong_length(): void
    {
        $this->expectException(RuntimeException::class);

        new Encryption('clave-demasiado-corta');
    }

    public function test_decrypt_rejects_corrupted_payload(): void
    {
        $encryption = new Encryption(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        $this->expectException(RuntimeException::class);

        $encryption->decrypt(base64_encode('esto-no-es-un-payload-valido'));
    }

    public function test_decrypt_fails_with_a_different_key(): void
    {
        $encryptedWithFirstKey = (new Encryption(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)))->encrypt('secreto');
        $otherEncryption = new Encryption(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        $this->expectException(RuntimeException::class);

        $otherEncryption->decrypt($encryptedWithFirstKey);
    }
}
