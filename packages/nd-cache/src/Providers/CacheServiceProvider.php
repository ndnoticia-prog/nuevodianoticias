<?php

declare(strict_types=1);

namespace NDCache\Providers;

use NDCache\Invalidation\CacheInvalidator;
use NDCache\PageCache\PageCacheMiddleware;
use NDCache\PageCache\PageCacheStore;
use NDCore\Config\Config;
use NDCore\Hooks\HookManager;
use NDCore\Providers\ServiceProvider;
use WP_Post;

final class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /** @var Config $config */
        $config = $this->container->make(Config::class);
        $config->loadDirectory(dirname(__DIR__, 2) . '/config');

        $this->container->singleton(PageCacheStore::class);
        $this->container->singleton(PageCacheMiddleware::class);
        $this->container->singleton(CacheInvalidator::class);
    }

    public function boot(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->container->make(HookManager::class);

        $hooks->addAction('template_redirect', function (): void {
            $this->container->make(PageCacheMiddleware::class)->maybeServeCached();
        }, 0);

        $hooks->addAction('save_post', function (int $postId, WP_Post $post): void {
            $this->container->make(CacheInvalidator::class)->handlePostSaved($postId, $post);
        }, 10, 2);
    }
}
