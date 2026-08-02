<?php

declare(strict_types=1);

namespace NDCache\Invalidation;

use NDCache\PageCache\PageCacheKey;
use NDCache\PageCache\PageCacheStore;
use WP_Post;

/**
 * Purga la caché de página cuando el contenido editorial cambia: la propia
 * página del artículo, la portada y sus categorías. Sin esto, la caché de
 * página completa serviría contenido obsoleto tras cada publicación/edición.
 */
final class CacheInvalidator
{
    public function __construct(private readonly PageCacheStore $store)
    {
    }

    public function handlePostSaved(int $postId, WP_Post $post): void
    {
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }

        if (! in_array($post->post_status, ['publish', 'trash'], true)) {
            return;
        }

        $this->purgeUrl((string) get_permalink($post));
        $this->purgeUrl((string) home_url('/'));

        foreach (get_the_category($post) as $category) {
            $this->purgeUrl((string) get_category_link($category));
        }
    }

    private function purgeUrl(string $url): void
    {
        if ($url === '') {
            return;
        }

        $this->store->forget(PageCacheKey::forUrl($url));
    }
}
