<?php

declare(strict_types=1);

namespace NDMedia\Tests\Unit\Podcast;

use Brain\Monkey\Functions;
use NDCore\Config\Config;
use NDMedia\Podcast\PodcastFeedEnhancer;
use NDMedia\Tests\BrainMonkeyTestCase;

final class PodcastFeedEnhancerTest extends BrainMonkeyTestCase {

	public function test_add_namespace_prints_itunes_xmlns(): void {
		ob_start();
		( new PodcastFeedEnhancer( new Config() ) )->addNamespace();
		$output = ob_get_clean();

		self::assertStringContainsString( 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"', $output );
	}

	public function test_add_enclosure_prints_enclosure_tag_when_audio_is_set(): void {
		Functions\expect( 'get_the_ID' )->andReturn( 42 );
		Functions\expect( 'get_post_meta' )->andReturnUsing(
			static function ( int $postId, string $key ): string {
				return match ( $key ) {
					'_nd_podcast_audio_url' => 'https://example.test/episodio.mp3',
					'_nd_podcast_audio_length' => '123456',
					default => '',
				};
			}
		);
		Functions\expect( 'esc_url' )->with( 'https://example.test/episodio.mp3' )->andReturn( 'https://example.test/episodio.mp3' );
		Functions\expect( 'get_the_title' )->with( 42 )->andReturn( 'Episodio 1' );
		Functions\expect( 'esc_html' )->with( 'Episodio 1' )->andReturn( 'Episodio 1' );

		ob_start();
		( new PodcastFeedEnhancer( new Config() ) )->addEnclosure();
		$output = ob_get_clean();

		self::assertStringContainsString( '<enclosure url="https://example.test/episodio.mp3" length="123456" type="audio/mpeg" />', $output );
		self::assertStringContainsString( '<itunes:title>Episodio 1</itunes:title>', $output );
	}

	public function test_add_enclosure_prints_nothing_without_audio(): void {
		Functions\expect( 'get_the_ID' )->andReturn( 42 );
		Functions\expect( 'get_post_meta' )->with( 42, '_nd_podcast_audio_url', true )->andReturn( '' );

		ob_start();
		( new PodcastFeedEnhancer( new Config() ) )->addEnclosure();
		$output = ob_get_clean();

		self::assertSame( '', $output );
	}
}
