<?php

declare(strict_types=1);

namespace NDAi\Providers;

use NDAi\Contracts\AiProvider;
use NDAi\Exceptions\AiProviderException;
use NDCore\Http\Client;
use NDCore\Http\Request;

final class GeminiProvider implements AiProvider
{
    public function __construct(
        private readonly Client $http,
        private readonly string $apiKey,
        private readonly string $model = 'gemini-2.0-flash',
    ) {
    }

    public function key(): string
    {
        return 'gemini';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function complete(string $prompt, array $options = []): string
    {
        if ($this->apiKey === '') {
            throw new AiProviderException('nd-ai: no hay una clave de API configurada para Gemini.');
        }

        $model = is_string($options['model'] ?? null) ? $options['model'] : $this->model;
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode($model),
            rawurlencode($this->apiKey)
        );

        $response = $this->http->send(new Request(
            method: 'POST',
            url: $url,
            headers: ['Content-Type' => 'application/json'],
            body: (string) wp_json_encode([
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
            ]),
            timeoutSeconds: 30,
        ));

        if ($response->failed()) {
            throw new AiProviderException('nd-ai: error al llamar a Gemini: ' . ($response->errorMessage ?? $response->body));
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (! is_string($text) || trim($text) === '') {
            throw new AiProviderException('nd-ai: respuesta de Gemini sin contenido de texto.');
        }

        return trim($text);
    }
}
