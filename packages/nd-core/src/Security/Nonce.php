<?php

declare(strict_types=1);

namespace NDCore\Security;

/**
 * Envoltorio tipado sobre el sistema de nonces de WordPress, usado para
 * proteger toda escritura (formularios admin, peticiones AJAX/REST) frente
 * a CSRF.
 */
final class Nonce
{
    public function create(string $action): string
    {
        return wp_create_nonce($action);
    }

    public function verify(string $nonce, string $action): bool
    {
        return wp_verify_nonce($nonce, $action) !== false;
    }

    /**
     * Verifica el nonce presente en `$_REQUEST[$queryArg]` para la acción dada.
     */
    public function verifyRequest(string $action, string $queryArg = '_wpnonce'): bool
    {
        $nonce = $_REQUEST[$queryArg] ?? null;

        return is_string($nonce) && $this->verify($nonce, $action);
    }

    public function field(string $action, string $name = '_wpnonce', bool $referer = true): string
    {
        return (string) wp_nonce_field($action, $name, $referer, false);
    }
}
