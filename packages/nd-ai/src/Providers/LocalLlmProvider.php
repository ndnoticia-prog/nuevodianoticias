<?php

declare(strict_types=1);

namespace NDAi\Providers;

use NDAi\Contracts\AiProvider;
use NDAi\Exceptions\AiProviderException;
use NDCore\Http\Client;
use NDCore\Http\Request;

/**
 * Cualquier servidor de LLM local con API compatible con OpenAI (Ollama,
 * LM Studio, vLLM, ...), configurado con una URL base propia.
 */
final class LocalLlmProvider implements AiProvider
{
    public function __construct(
        private readonly Client $http,
        private readonly string $baseUrl,
        private readonly string $model = 'llama3',
    ) {
    }

    public function key(): string
    {
        return 'local';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function complete(string $prompt, array $options = []): string
    {
        if ($this->baseUrl === '') {
            throw new AiProviderException('nd-ai: no hay una URL configurada para el proveedor local.');
        }

        $response = $this->http->send(new Request(
            method: 'POST',
            url: rtrim($this->baseUrl, '/') . '/chat/completions',
            headers: ['Content-Type' => 'application/json'],
            body: (string) wp_json_encode([
                'model' => $options['model'] ?? $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]),
            timeoutSeconds: 60,
        ));

        if ($response->failed()) {
            throw new AiProviderException('nd-ai: error al llamar al proveedor local: ' . ($response->errorMessage ?? $response->body));
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            throw new AiProviderException('nd-ai: respuesta del proveedor local sin contenido de texto.');
        }

        return trim($content);
    }
}
