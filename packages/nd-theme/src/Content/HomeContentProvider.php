<?php

declare(strict_types=1);

namespace NDTheme\Content;

use NDBuilder\Block;
use WP_Post;
use WP_Query;

/**
 * Traduce contenido real de WordPress (WP_Query) al modelo de datos de
 * bloques de nd-builder para componer la portada. Es el único lugar de
 * nd-theme que decide "qué aparece dónde"; la presentación (HTML) sigue
 * viviendo en `template-parts/blocks/*.php`.
 */
final class HomeContentProvider
{
    private const NOTICIAS_COUNT = 6;
    private const BREAKING_COUNT = 5;
    private const DEFAULT_THUMBNAIL_SIZE = 'large';

    /**
     * Mismo nombre de tamaño que registra `NDDiscover\ImageSizes::FEATURED`
     * (>=1200px de ancho, requisito de Google Discover), usado solo para la
     * imagen del bloque hero. Referenciado como cadena literal a propósito
     * para no acoplar nd-theme a nd-discover: si el tamaño no existe,
     * postSummary() recurre a "large".
     */
    private const HERO_THUMBNAIL_SIZE = 'nd-discover-featured';

    /**
     * Categoría usada como marcador editorial de "última hora" hasta que
     * nd-workflow aporte un estado editorial dedicado.
     */
    private const BREAKING_CATEGORY_SLUG = 'ultima-hora';

    /**
     * @return list<Block>
     */
    public function blocksForHomepage(): array
    {
        $blocks = [];

        $breaking = $this->breakingBlock();

        if ($breaking !== null) {
            $blocks[] = $breaking;
        }

        $hero = $this->heroBlock();
        $excludeId = null;

        if ($hero !== null) {
            $blocks[] = $hero;
            $excludeId = (int) $hero->attribute('post_id');
        }

        $blocks[] = $this->noticiasBlock($excludeId);

        return $blocks;
    }

    private function heroBlock(): ?Block
    {
        $query = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        ]);

        if (! $query->have_posts()) {
            return null;
        }

        return new Block('hero', 'home-hero', $this->postSummary($query->posts[0], self::HERO_THUMBNAIL_SIZE));
    }

    private function noticiasBlock(?int $excludePostId): Block
    {
        $args = [
            'post_type' => 'post',
            'posts_per_page' => self::NOTICIAS_COUNT,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        ];

        if ($excludePostId !== null) {
            $args['post__not_in'] = [$excludePostId];
        }

        $query = new WP_Query($args);

        $items = array_map(
            fn (WP_Post $post): array => $this->postSummary($post),
            $query->posts
        );

        return new Block('noticias', 'home-noticias', ['items' => $items]);
    }

    private function breakingBlock(): ?Block
    {
        $category = get_category_by_slug(self::BREAKING_CATEGORY_SLUG);

        if ($category === false) {
            return null;
        }

        $query = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => self::BREAKING_COUNT,
            'post_status' => 'publish',
            'category__in' => [$category->term_id],
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        ]);

        if (! $query->have_posts()) {
            return null;
        }

        $items = array_map(
            static fn (WP_Post $post): array => [
                'title' => get_the_title($post),
                'permalink' => (string) get_permalink($post),
            ],
            $query->posts
        );

        return new Block('breaking', 'home-breaking', ['items' => $items]);
    }

    /**
     * @return array<string, mixed>
     */
    private function postSummary(WP_Post $post, string $thumbnailSize = self::DEFAULT_THUMBNAIL_SIZE): array
    {
        $thumbnail = get_the_post_thumbnail_url($post, $thumbnailSize);

        if ($thumbnail === false && $thumbnailSize !== self::DEFAULT_THUMBNAIL_SIZE) {
            $thumbnail = get_the_post_thumbnail_url($post, self::DEFAULT_THUMBNAIL_SIZE);
        }

        return [
            'post_id' => $post->ID,
            'title' => get_the_title($post),
            'excerpt' => get_the_excerpt($post),
            'permalink' => (string) get_permalink($post),
            'thumbnail' => $thumbnail === false ? null : $thumbnail,
            'author' => get_the_author_meta('display_name', (int) $post->post_author),
            'date' => get_the_date('', $post),
        ];
    }
}
