<?php

declare(strict_types=1);

namespace NDSearch\Indexing;

use NDSearch\Repository\SearchIndexRepository;
use WP_Post;

final class SearchIndexer
{
    public function __construct(private readonly SearchIndexRepository $repository)
    {
    }

    public function handleSaved(int $postId, WP_Post $post): void
    {
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }

        if ($post->post_type !== 'post' || $post->post_status !== 'publish') {
            $this->repository->delete($postId);

            return;
        }

        $contentText = wp_strip_all_tags(strip_shortcodes($post->post_content));

        $this->repository->upsert($postId, get_the_title($post), $contentText);
    }

    public function handleDeleted(int $postId): void
    {
        $this->repository->delete($postId);
    }
}
