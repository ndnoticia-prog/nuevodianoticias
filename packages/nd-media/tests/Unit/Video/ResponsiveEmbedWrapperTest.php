<?php

declare(strict_types=1);

namespace NDMedia\Tests\Unit\Video;

use NDMedia\Video\ResponsiveEmbedWrapper;
use PHPUnit\Framework\TestCase;

final class ResponsiveEmbedWrapperTest extends TestCase {

	public function test_wraps_iframe_embeds(): void {
		$html = '<iframe src="https://www.youtube.com/embed/xyz"></iframe>';

		self::assertSame(
			'<div class="nd-video-embed">' . $html . '</div>',
			( new ResponsiveEmbedWrapper() )->wrap( $html )
		);
	}

	public function test_leaves_non_iframe_embeds_untouched(): void {
		$html = '<blockquote class="twitter-tweet">...</blockquote>';

		self::assertSame( $html, ( new ResponsiveEmbedWrapper() )->wrap( $html ) );
	}

	public function test_leaves_empty_string_untouched(): void {
		self::assertSame( '', ( new ResponsiveEmbedWrapper() )->wrap( '' ) );
	}
}
