<?php

declare(strict_types=1);

namespace NDCache\PageCache;

/**
 * Deriva la misma clave de caché a partir de una URL, tanto para servir
 * ({@see PageCacheMiddleware}) como para invalidar ({@see \NDCache\Invalidation\CacheInvalidator}).
 */
final class PageCacheKey
{
    private function __construct()
    {
    }

    public static function forUrl(string $url): string
    {
        return 'page:' . md5($url);
    }

    public static function forCurrentRequest(): string
    {
        $scheme = is_ssl() ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_HOST'])) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '/';

        return self::forUrl($scheme . $host . $uri);
    }
}
