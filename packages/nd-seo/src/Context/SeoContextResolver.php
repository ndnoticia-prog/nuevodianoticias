<?php

declare(strict_types=1);

namespace NDSeo\Context;

use NDCore\Config\Config;
use WP_Post;

/**
 * Traduce el estado global de WordPress (`is_singular()`, `is_search()`,
 * `get_queried_object()`, ...) a un único {@see SeoContext} inmutable.
 */
final class SeoContextResolver
{
    private const DESCRIPTION_MAX_LENGTH = 160;

    /**
     * Mismo nombre de tamaño que registra `NDDiscover\ImageSizes::FEATURED`
     * (>=1200px de ancho, requisito de Google Discover). Se referencia como
     * cadena literal a propósito, no como dependencia de Composer, para no
     * acoplar nd-seo a nd-discover: si el tamaño no existe (nd-discover no
     * está activo), WordPress simplemente no lo encuentra y se recurre a
     * "large".
     */
    private const FEATURED_IMAGE_SIZE = 'nd-discover-featured';

    public function __construct(private readonly Config $config)
    {
    }

    public function resolve(): SeoContext
    {
        if (is_singular() && ($post = get_queried_object()) instanceof WP_Post) {
            return $this->forSingular($post);
        }

        if (is_404()) {
            return $this->forNotFound();
        }

        if (is_home() || is_front_page()) {
            return $this->forHome();
        }

        return $this->forListing(is_search());
    }

    private function forSingular(WP_Post $post): SeoContext
    {
        $thumbnail = $this->featuredImageUrl($post);

        return new SeoContext(
            title: get_the_title($post) . ' - ' . (string) get_bloginfo('name'),
            description: $this->plainExcerpt($post),
            canonicalUrl: (string) get_permalink($post),
            imageUrl: $thumbnail !== false ? $thumbnail : $this->defaultImage(),
            type: 'article',
            isSingular: true,
            noindex: get_post_status($post) !== 'publish',
            post: $post,
        );
    }

    private function forHome(): SeoContext
    {
        $description = (string) get_bloginfo('description');

        return new SeoContext(
            title: (string) get_bloginfo('name') . ($description !== '' ? ' - ' . $description : ''),
            description: $description,
            canonicalUrl: (string) home_url('/'),
            imageUrl: $this->defaultImage(),
            type: 'website',
            isSingular: false,
            noindex: false,
        );
    }

    private function forListing(bool $isSearch): SeoContext
    {
        $title = $isSearch
            ? sprintf(
                /* translators: %s: término buscado */
                __('Resultados de búsqueda para "%s"', 'nd-seo'),
                get_search_query()
            )
            : (string) wp_strip_all_tags((string) get_the_archive_title());

        return new SeoContext(
            title: $title . ' - ' . (string) get_bloginfo('name'),
            description: $isSearch ? '' : (string) wp_strip_all_tags((string) get_the_archive_description()),
            canonicalUrl: (string) get_pagenum_link(1),
            imageUrl: $this->defaultImage(),
            type: 'website',
            isSingular: false,
            noindex: $isSearch,
        );
    }

    private function forNotFound(): SeoContext
    {
        return new SeoContext(
            title: __('Página no encontrada', 'nd-seo') . ' - ' . (string) get_bloginfo('name'),
            description: '',
            canonicalUrl: '',
            imageUrl: $this->defaultImage(),
            type: 'website',
            isSingular: false,
            noindex: true,
        );
    }

    private function featuredImageUrl(WP_Post $post): string|false
    {
        $url = get_the_post_thumbnail_url($post, self::FEATURED_IMAGE_SIZE);

        return $url !== false ? $url : get_the_post_thumbnail_url($post, 'large');
    }

    private function plainExcerpt(WP_Post $post): string
    {
        $raw = $post->post_excerpt !== '' ? $post->post_excerpt : strip_shortcodes($post->post_content);
        $plain = (string) wp_strip_all_tags($raw);
        $plain = trim((string) preg_replace('/\s+/', ' ', $plain));

        if (mb_strlen($plain) <= self::DESCRIPTION_MAX_LENGTH) {
            return $plain;
        }

        return rtrim(mb_substr($plain, 0, self::DESCRIPTION_MAX_LENGTH - 3)) . '...';
    }

    private function defaultImage(): ?string
    {
        $image = $this->config->get('seo.social.default_image');

        return is_string($image) && $image !== '' ? $image : null;
    }
}
