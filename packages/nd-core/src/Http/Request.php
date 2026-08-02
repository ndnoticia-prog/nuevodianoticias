<?php

declare(strict_types=1);

namespace NDCore\Http;

/**
 * Petición HTTP saliente inmutable, consumida por {@see Client}.
 */
final class Request
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $query
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly array $headers = [],
        public readonly array $query = [],
        public readonly string $body = '',
        public readonly int $timeoutSeconds = 15,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public static function json(string $method, string $url, array $data = [], array $headers = []): self
    {
        return new self(
            method: $method,
            url: $url,
            headers: $headers + ['Content-Type' => 'application/json'],
            body: $data === [] ? '' : (string) json_encode($data, JSON_THROW_ON_ERROR),
        );
    }

    public function withUrl(string $url): self
    {
        return new self($this->method, $url, $this->headers, $this->query, $this->body, $this->timeoutSeconds);
    }

    public function fullUrl(): string
    {
        if ($this->query === []) {
            return $this->url;
        }

        $separator = str_contains($this->url, '?') ? '&' : '?';

        return $this->url . $separator . http_build_query($this->query);
    }
}
