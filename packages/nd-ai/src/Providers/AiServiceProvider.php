<?php

declare(strict_types=1);

namespace NDAi\Providers;

use NDAi\AiManager;
use NDAi\RestApi\AiController;
use NDAi\Settings\ApiKeyStore;
use NDAi\Tasks\ContentAssistant;
use NDCore\Config\Config;
use NDCore\Hooks\HookManager;
use NDCore\Http\Client;
use NDCore\Providers\ServiceProvider;

final class AiServiceProvider extends ServiceProvider {

	public function register(): void {
		/** @var Config $config */
		$config = $this->container->make( Config::class );
		$config->loadDirectory( dirname( __DIR__, 2 ) . '/config' );

		$this->container->singleton( ApiKeyStore::class );

		$this->container->singleton(
			OpenAiProvider::class,
			function () use ( $config ): OpenAiProvider {
				/** @var ApiKeyStore $keys */
				$keys = $this->container->make( ApiKeyStore::class );

				/** @var Client $http */
				$http = $this->container->make( Client::class );

				return new OpenAiProvider(
					$http,
					$keys->get( 'openai' ),
					(string) $config->get( 'ai.models.openai', 'gpt-4o-mini' ),
				);
			}
		);

		$this->container->singleton(
			ClaudeProvider::class,
			function () use ( $config ): ClaudeProvider {
				/** @var ApiKeyStore $keys */
				$keys = $this->container->make( ApiKeyStore::class );

				/** @var Client $http */
				$http = $this->container->make( Client::class );

				return new ClaudeProvider(
					$http,
					$keys->get( 'claude' ),
					(string) $config->get( 'ai.models.claude', 'claude-sonnet-5' ),
				);
			}
		);

		$this->container->singleton(
			GeminiProvider::class,
			function () use ( $config ): GeminiProvider {
				/** @var ApiKeyStore $keys */
				$keys = $this->container->make( ApiKeyStore::class );

				/** @var Client $http */
				$http = $this->container->make( Client::class );

				return new GeminiProvider(
					$http,
					$keys->get( 'gemini' ),
					(string) $config->get( 'ai.models.gemini', 'gemini-2.0-flash' ),
				);
			}
		);

		$this->container->singleton(
			DeepSeekProvider::class,
			function () use ( $config ): DeepSeekProvider {
				/** @var ApiKeyStore $keys */
				$keys = $this->container->make( ApiKeyStore::class );

				/** @var Client $http */
				$http = $this->container->make( Client::class );

				return new DeepSeekProvider(
					$http,
					$keys->get( 'deepseek' ),
					(string) $config->get( 'ai.models.deepseek', 'deepseek-chat' ),
				);
			}
		);

		$this->container->singleton(
			LocalLlmProvider::class,
			function () use ( $config ): LocalLlmProvider {
				/** @var Client $http */
				$http = $this->container->make( Client::class );

				return new LocalLlmProvider(
					$http,
					(string) $config->get( 'ai.local_base_url', '' ),
					(string) $config->get( 'ai.models.local', 'llama3' ),
				);
			}
		);

		$this->container->singleton(
			AiManager::class,
			function () use ( $config ): AiManager {
				/** @var OpenAiProvider $openAi */
				$openAi = $this->container->make( OpenAiProvider::class );

				/** @var ClaudeProvider $claude */
				$claude = $this->container->make( ClaudeProvider::class );

				/** @var GeminiProvider $gemini */
				$gemini = $this->container->make( GeminiProvider::class );

				/** @var DeepSeekProvider $deepSeek */
				$deepSeek = $this->container->make( DeepSeekProvider::class );

				/** @var LocalLlmProvider $localLlm */
				$localLlm = $this->container->make( LocalLlmProvider::class );

				return new AiManager(
					providers: array( $openAi, $claude, $gemini, $deepSeek, $localLlm ),
					defaultProviderKey: (string) $config->get( 'ai.default_provider', 'openai' ),
				);
			}
		);

		$this->container->singleton( ContentAssistant::class );
		$this->container->singleton( AiController::class );
	}

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->make( HookManager::class );

		$hooks->addFilter(
			'nd_core/rest_controllers',
			static function ( array $controllers ): array {
				$controllers[] = AiController::class;

				return $controllers;
			}
		);
	}
}
