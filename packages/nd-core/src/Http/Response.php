<?php

declare(strict_types=1);

namespace NDCore\Http;

use JsonException;

/**
 * Respuesta HTTP inmutable devuelta por {@see Client}.
 */
final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body,
        public readonly bool $isError,
        public readonly ?string $errorMessage = null,
    ) {
    }

    public function successful(): bool
    {
        return ! $this->isError && $this->status >= 200 && $this->status < 300;
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    /**
     * @return array<mixed>
     */
    public function json(): array
    {
        if ($this->body === '') {
            return [];
        }

        try {
            /** @var array<mixed> $decoded */
            $decoded = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return $decoded;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
