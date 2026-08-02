<?php

declare(strict_types=1);

namespace NDCache\Tests\Unit\PageCache;

use Brain\Monkey\Functions;
use NDCache\PageCache\PageCacheKey;
use NDCache\Tests\BrainMonkeyTestCase;

final class PageCacheKeyTest extends BrainMonkeyTestCase
{
    public function test_for_url_is_deterministic(): void
    {
        self::assertSame(PageCacheKey::forUrl('https://example.test/'), PageCacheKey::forUrl('https://example.test/'));
        self::assertNotSame(PageCacheKey::forUrl('https://example.test/a'), PageCacheKey::forUrl('https://example.test/b'));
    }

    public function test_for_url_is_prefixed(): void
    {
        self::assertStringStartsWith('page:', PageCacheKey::forUrl('https://example.test/'));
    }

    public function test_for_current_request_combines_scheme_host_and_uri(): void
    {
        Functions\expect('is_ssl')->once()->andReturn(true);
        Functions\expect('sanitize_text_field')->andReturnUsing(static fn (string $v): string => $v);
        Functions\expect('wp_unslash')->andReturnUsing(static fn (string $v): string => $v);

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['REQUEST_URI'] = '/noticia/algo';

        self::assertSame(
            PageCacheKey::forUrl('https://example.test/noticia/algo'),
            PageCacheKey::forCurrentRequest()
        );

        unset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
    }
}
