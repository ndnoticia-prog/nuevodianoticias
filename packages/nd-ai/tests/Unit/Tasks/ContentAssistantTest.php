<?php

declare(strict_types=1);

namespace NDAi\Tests\Unit\Tasks;

use NDAi\AiManager;
use NDAi\Contracts\AiProvider;
use NDAi\Tasks\ContentAssistant;
use PHPUnit\Framework\TestCase;

final class ContentAssistantTestRecordingProvider implements AiProvider {

	public ?string $lastPrompt = null;

	public function __construct( private readonly string $reply ) {
	}

	public function key(): string {
		return 'stub';
	}

	public function complete( string $prompt, array $options = array() ): string {
		$this->lastPrompt = $prompt;

		return $this->reply;
	}
}

final class ContentAssistantTest extends TestCase {

	private function assistant( string $reply ): array {
		$provider = new ContentAssistantTestRecordingProvider( $reply );
		$manager  = new AiManager( array( $provider ), 'stub' );

		return array( new ContentAssistant( $manager ), $provider );
	}

	public function test_generate_headlines_splits_numbered_lines(): void {
		[$assistant] = $this->assistant( "1. Primer titular\n2) Segundo titular\n- Tercer titular" );

		self::assertSame(
			array( 'Primer titular', 'Segundo titular', 'Tercer titular' ),
			$assistant->generateHeadlines( 'cuerpo del artículo' )
		);
	}

	public function test_generate_headlines_prompt_mentions_the_article_and_the_requested_count(): void {
		[$assistant, $provider] = $this->assistant( 'Titular único' );

		$assistant->generateHeadlines( 'cuerpo del artículo', 3 );

		self::assertStringContainsString( '3 titulares', (string) $provider->lastPrompt );
		self::assertStringContainsString( 'cuerpo del artículo', (string) $provider->lastPrompt );
	}

	public function test_generate_tags_splits_comma_separated_list(): void {
		[$assistant] = $this->assistant( 'deportes, fútbol ,  colombia' );

		self::assertSame( array( 'deportes', 'fútbol', 'colombia' ), $assistant->generateTags( 'texto' ) );
	}

	public function test_suggest_categories_only_returns_categories_from_the_available_list(): void {
		[$assistant] = $this->assistant( 'Deportes, Inventada, Economía' );

		self::assertSame(
			array( 'Deportes', 'Economía' ),
			$assistant->suggestCategories( 'texto', array( 'Deportes', 'Economía', 'Cultura' ) )
		);
	}

	public function test_suggest_categories_returns_empty_without_available_categories(): void {
		[$assistant, $provider] = $this->assistant( 'no debería llamarse' );

		self::assertSame( array(), $assistant->suggestCategories( 'texto', array() ) );
		self::assertNull( $provider->lastPrompt );
	}

	public function test_generate_social_post_includes_platform_specific_guidance_and_url(): void {
		[$assistant, $provider] = $this->assistant( 'post generado' );

		$assistant->generateSocialPost( 'instagram', 'texto', 'https://example.test/articulo' );

		self::assertStringContainsString( 'hashtags', (string) $provider->lastPrompt );
		self::assertStringContainsString( 'https://example.test/articulo', (string) $provider->lastPrompt );
	}

	public function test_generate_video_script_prompt_scales_word_count_with_duration(): void {
		[$assistant, $provider] = $this->assistant( 'guion' );

		$assistant->generateVideoScript( 'texto', 90 );

		self::assertStringContainsString( '90 segundos', (string) $provider->lastPrompt );
		self::assertStringContainsString( '225 palabras', (string) $provider->lastPrompt );
	}
}
