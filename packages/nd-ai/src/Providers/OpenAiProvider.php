<?php

declare(strict_types=1);

namespace NDAi\Providers;

use NDAi\Contracts\AiProvider;
use NDAi\Exceptions\AiProviderException;
use NDCore\Http\Client;
use NDCore\Http\Request;

final class OpenAiProvider implements AiProvider
{
    public function __construct(
        private readonly Client $http,
        private readonly string $apiKey,
        private readonly string $model = 'gpt-4o-mini',
    ) {
    }

    public function key(): string
    {
        return 'openai';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function complete(string $prompt, array $options = []): string
    {
        if ($this->apiKey === '') {
            throw new AiProviderException('nd-ai: no hay una clave de API configurada para OpenAI.');
        }

        $response = $this->http->send(new Request(
            method: 'POST',
            url: 'https://api.openai.com/v1/chat/completions',
            headers: [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            body: (string) wp_json_encode([
                'model' => $options['model'] ?? $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $options['temperature'] ?? 0.7,
            ]),
            timeoutSeconds: 30,
        ));

        if ($response->failed()) {
            throw new AiProviderException('nd-ai: error al llamar a OpenAI: ' . ($response->errorMessage ?? $response->body));
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            throw new AiProviderException('nd-ai: respuesta de OpenAI sin contenido de texto.');
        }

        return trim($content);
    }
}
