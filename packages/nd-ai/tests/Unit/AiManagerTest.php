<?php

declare(strict_types=1);

namespace NDAi\Tests\Unit;

use NDAi\AiManager;
use NDAi\Contracts\AiProvider;
use NDAi\Exceptions\AiProviderException;
use PHPUnit\Framework\TestCase;

final class AiManagerTestStubProvider implements AiProvider {

	public function __construct( private readonly string $providerKey, private readonly string $reply = 'ok' ) {
	}

	public function key(): string {
		return $this->providerKey;
	}

	public function complete( string $prompt, array $options = array() ): string {
		return $this->reply . ':' . $prompt;
	}
}

final class AiManagerTest extends TestCase {

	public function test_complete_uses_the_default_provider(): void {
		$manager = new AiManager(
			providers: array( new AiManagerTestStubProvider( 'openai', 'openai-reply' ) ),
			defaultProviderKey: 'openai',
		);

		self::assertSame( 'openai-reply:hola', $manager->complete( 'hola' ) );
	}

	public function test_complete_can_target_a_specific_provider(): void {
		$manager = new AiManager(
			providers: array(
				new AiManagerTestStubProvider( 'openai', 'openai-reply' ),
				new AiManagerTestStubProvider( 'claude', 'claude-reply' ),
			),
			defaultProviderKey: 'openai',
		);

		self::assertSame( 'claude-reply:hola', $manager->complete( 'hola', array(), 'claude' ) );
	}

	public function test_provider_throws_for_unknown_key(): void {
		$manager = new AiManager( providers: array(), defaultProviderKey: 'openai' );

		$this->expectException( AiProviderException::class );

		$manager->provider( 'openai' );
	}
}
