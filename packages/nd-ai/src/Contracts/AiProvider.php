<?php

declare(strict_types=1);

namespace NDAi\Contracts;

use NDAi\Exceptions\AiProviderException;

interface AiProvider
{
    /**
     * Identificador corto y estable del proveedor (p. ej. "openai").
     */
    public function key(): string;

    /**
     * @param array<string, mixed> $options Sobrescribe valores por defecto (p. ej. "model", "temperature").
     *
     * @throws AiProviderException Si no hay credenciales configuradas o la llamada falla.
     */
    public function complete(string $prompt, array $options = []): string;
}
