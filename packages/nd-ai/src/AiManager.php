<?php

declare(strict_types=1);

namespace NDAi;

use NDAi\Contracts\AiProvider;
use NDAi\Exceptions\AiProviderException;

final class AiManager
{
    /**
     * @param list<AiProvider> $providers
     */
    public function __construct(
        private readonly array $providers,
        private readonly string $defaultProviderKey,
    ) {
    }

    public function provider(?string $key = null): AiProvider
    {
        $key ??= $this->defaultProviderKey;

        foreach ($this->providers as $provider) {
            if ($provider->key() === $key) {
                return $provider;
            }
        }

        throw new AiProviderException(sprintf('nd-ai: el proveedor "%s" no está registrado.', $key));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function complete(string $prompt, array $options = [], ?string $providerKey = null): string
    {
        return $this->provider($providerKey)->complete($prompt, $options);
    }
}
