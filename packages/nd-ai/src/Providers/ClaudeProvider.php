<?php

declare(strict_types=1);

namespace NDAi\Providers;

use NDAi\Contracts\AiProvider;
use NDAi\Exceptions\AiProviderException;
use NDCore\Http\Client;
use NDCore\Http\Request;
use NDCore\Support\Arr;

final class ClaudeProvider implements AiProvider {

	public function __construct(
		private readonly Client $http,
		private readonly string $apiKey,
		private readonly string $model = 'claude-sonnet-5',
	) {
	}

	public function key(): string {
		return 'claude';
	}

	/**
	 * @param array<string, mixed> $options
	 */
	public function complete( string $prompt, array $options = array() ): string {
		if ( $this->apiKey === '' ) {
			throw new AiProviderException( 'nd-ai: no hay una clave de API configurada para Claude.' );
		}

		$response = $this->http->send(
			new Request(
				method: 'POST',
				url: 'https://api.anthropic.com/v1/messages',
				headers: array(
					'x-api-key'         => $this->apiKey,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				body: (string) wp_json_encode(
					array(
						'model'      => $options['model'] ?? $this->model,
						'max_tokens' => $options['max_tokens'] ?? 1024,
						'messages'   => array(
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
					)
				),
				timeoutSeconds: 30,
			)
		);

		if ( $response->failed() ) {
			throw new AiProviderException( 'nd-ai: error al llamar a Claude: ' . ( $response->errorMessage ?? $response->body ) );
		}

		$data = $response->json();
		$text = Arr::get( $data, 'content.0.text' );

		if ( ! is_string( $text ) || trim( $text ) === '' ) {
			throw new AiProviderException( 'nd-ai: respuesta de Claude sin contenido de texto.' );
		}

		return trim( $text );
	}
}
