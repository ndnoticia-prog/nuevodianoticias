<?php

declare(strict_types=1);

namespace NDSeo\Breadcrumbs;

use WP_Post;

/**
 * Construye el camino de migas de pan del contexto actual. Usado tanto por
 * {@see \NDSeo\Schema\BreadcrumbListSchema} (JSON-LD) como por
 * {@see BreadcrumbRenderer} (HTML) para no duplicar la misma lógica.
 */
final class BreadcrumbBuilder
{
    /**
     * @return list<Breadcrumb>
     */
    public function build(): array
    {
        $trail = [new Breadcrumb(__('Inicio', 'nd-seo'), (string) home_url('/'))];

        if (is_singular() && ($post = get_queried_object()) instanceof WP_Post) {
            return [...$trail, ...$this->forSingular($post)];
        }

        if (is_search()) {
            $trail[] = new Breadcrumb(
                sprintf(
                    /* translators: %s: término buscado */
                    __('Resultados de búsqueda para "%s"', 'nd-seo'),
                    get_search_query()
                ),
                (string) get_search_link()
            );

            return $trail;
        }

        if (! is_home() && ! is_front_page()) {
            $title = wp_strip_all_tags((string) get_the_archive_title());

            if ($title !== '') {
                $trail[] = new Breadcrumb($title, (string) get_pagenum_link(1));
            }
        }

        return $trail;
    }

    /**
     * @return list<Breadcrumb>
     */
    private function forSingular(WP_Post $post): array
    {
        $items = [];
        $categories = get_the_category($post);

        if ($categories !== []) {
            $primary = $categories[0];
            $items[] = new Breadcrumb($primary->name, (string) get_category_link($primary));
        }

        $items[] = new Breadcrumb(get_the_title($post), (string) get_permalink($post));

        return $items;
    }
}
