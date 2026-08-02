<?php

declare(strict_types=1);

namespace NDCache\PageCache;

use NDCore\Config\Config;

/**
 * Sirve HTML de página completa ya cacheado (sin ejecutar el resto de
 * WordPress) o, si no hay caché, captura la salida vía output buffering y
 * la almacena para la siguiente petición. Nunca cachea peticiones de
 * usuarios autenticados, admin, AJAX/REST/cron, ni búsquedas.
 */
final class PageCacheMiddleware
{
    public function __construct(
        private readonly PageCacheStore $store,
        private readonly Config $config,
    ) {
    }

    public function maybeServeCached(): void
    {
        if (! $this->isCacheable()) {
            return;
        }

        $key = PageCacheKey::forCurrentRequest();
        $cached = $this->store->get($key);

        if ($cached !== null) {
            header('X-ND-Cache: HIT');
            echo $cached;
            exit;
        }

        header('X-ND-Cache: MISS');
        ob_start(function (string $html) use ($key): string {
            if ($html !== '' && ! is_404()) {
                $this->store->put($key, $html);
            }

            return $html;
        });
    }

    private function isCacheable(): bool
    {
        if (! (bool) $this->config->get('cache.page_cache.enabled', true)) {
            return false;
        }

        if (is_admin() || is_user_logged_in() || is_search()) {
            return false;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return false;
        }

        if ((defined('DOING_AJAX') && DOING_AJAX)
            || (defined('DOING_CRON') && DOING_CRON)
            || (defined('REST_REQUEST') && REST_REQUEST)
        ) {
            return false;
        }

        return true;
    }
}
