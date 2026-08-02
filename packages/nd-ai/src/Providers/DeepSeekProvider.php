<?php

declare(strict_types=1);

namespace NDAi\Providers;

use NDAi\Contracts\AiProvider;
use NDAi\Exceptions\AiProviderException;
use NDCore\Http\Client;
use NDCore\Http\Request;

/**
 * DeepSeek expone una API compatible con el formato de OpenAI.
 */
final class DeepSeekProvider implements AiProvider
{
    public function __construct(
        private readonly Client $http,
        private readonly string $apiKey,
        private readonly string $model = 'deepseek-chat',
    ) {
    }

    public function key(): string
    {
        return 'deepseek';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function complete(string $prompt, array $options = []): string
    {
        if ($this->apiKey === '') {
            throw new AiProviderException('nd-ai: no hay una clave de API configurada para DeepSeek.');
        }

        $response = $this->http->send(new Request(
            method: 'POST',
            url: 'https://api.deepseek.com/chat/completions',
            headers: [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            body: (string) wp_json_encode([
                'model' => $options['model'] ?? $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]),
            timeoutSeconds: 30,
        ));

        if ($response->failed()) {
            throw new AiProviderException('nd-ai: error al llamar a DeepSeek: ' . ($response->errorMessage ?? $response->body));
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            throw new AiProviderException('nd-ai: respuesta de DeepSeek sin contenido de texto.');
        }

        return trim($content);
    }
}
