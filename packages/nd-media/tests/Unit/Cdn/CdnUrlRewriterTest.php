<?php

declare(strict_types=1);

namespace NDMedia\Tests\Unit\Cdn;

use Brain\Monkey\Functions;
use NDCore\Config\Config;
use NDMedia\Cdn\CdnUrlRewriter;
use NDMedia\Tests\BrainMonkeyTestCase;

final class CdnUrlRewriterTest extends BrainMonkeyTestCase
{
    public function test_rewrites_attachment_url_to_cdn_domain(): void
    {
        Functions\when('wp_get_upload_dir')->justReturn(['baseurl' => 'https://example.test/wp-content/uploads']);

        $config = new Config(['media' => ['cdn_url' => 'https://cdn.example.test']]);
        $rewriter = new CdnUrlRewriter($config);

        self::assertSame(
            'https://cdn.example.test/2026/01/foto.jpg',
            $rewriter->filterAttachmentUrl('https://example.test/wp-content/uploads/2026/01/foto.jpg')
        );
    }

    public function test_rewrites_urls_found_inside_content(): void
    {
        Functions\when('wp_get_upload_dir')->justReturn(['baseurl' => 'https://example.test/wp-content/uploads']);

        $config = new Config(['media' => ['cdn_url' => 'https://cdn.example.test']]);
        $rewriter = new CdnUrlRewriter($config);

        $content = '<img src="https://example.test/wp-content/uploads/foto.jpg">';

        self::assertSame(
            '<img src="https://cdn.example.test/foto.jpg">',
            $rewriter->filterContent($content)
        );
    }

    public function test_leaves_urls_untouched_without_configured_cdn(): void
    {
        $config = new Config();
        $rewriter = new CdnUrlRewriter($config);

        $url = 'https://example.test/wp-content/uploads/foto.jpg';

        self::assertSame($url, $rewriter->filterAttachmentUrl($url));
    }
}
