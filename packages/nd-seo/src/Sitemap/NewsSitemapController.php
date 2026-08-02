<?php

declare(strict_types=1);

namespace NDSeo\Sitemap;

use NDCore\Config\Config;
use WP_Query;

/**
 * Sitemap de Google News (`/sitemap-news.xml`), un formato distinto al
 * sitemap general (namespace `news:`, solo artículos de las últimas horas).
 * WordPress core ya expone un sitemap general completo y mantenido
 * (`wp-sitemap.xml`) desde la 5.5, así que nd-seo deliberadamente NO lo
 * reimplementa — solo añade lo que core no cubre. Ver docs/Architecture.md.
 */
final class NewsSitemapController
{
    public const QUERY_VAR = 'nd_news_sitemap';

    public function __construct(private readonly Config $config)
    {
    }

    public function registerRewriteRule(): void
    {
        add_rewrite_rule('^sitemap-news\.xml$', 'index.php?' . self::QUERY_VAR . '=1', 'top');
    }

    /**
     * @param list<string> $vars
     *
     * @return list<string>
     */
    public function registerQueryVar(array $vars): array
    {
        $vars[] = self::QUERY_VAR;

        return $vars;
    }

    public function maybeRender(): void
    {
        if (! get_query_var(self::QUERY_VAR)) {
            return;
        }

        if (! (bool) $this->config->get('seo.news_sitemap.enabled', true)) {
            status_header(404);
            exit;
        }

        header('Content-Type: application/xml; charset=' . get_bloginfo('charset'));
        echo $this->buildXml();
        exit;
    }

    private function buildXml(): string
    {
        $maxAgeHours = (int) $this->config->get('seo.news_sitemap.max_age_hours', 48);
        $maxItems = (int) $this->config->get('seo.news_sitemap.max_items', 1000);
        $publicationName = $this->config->get('seo.news_sitemap.publication_name');
        $publicationName = is_string($publicationName) && $publicationName !== ''
            ? $publicationName
            : (string) get_bloginfo('name');
        $language = (string) $this->config->get('seo.news_sitemap.publication_language', 'es');

        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $maxItems,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'date_query' => [
                ['after' => $maxAgeHours . ' hours ago'],
            ],
        ]);

        $items = '';

        foreach ($query->posts as $post) {
            $items .= sprintf(
                '<url><loc>%s</loc><news:news><news:publication><news:name>%s</news:name>' .
                '<news:language>%s</news:language></news:publication>' .
                '<news:publication_date>%s</news:publication_date><news:title>%s</news:title>' .
                '</news:news></url>',
                esc_url((string) get_permalink($post)),
                esc_html($publicationName),
                esc_html($language),
                esc_html(get_the_date(DATE_W3C, $post)),
                esc_html(wp_strip_all_tags(get_the_title($post)))
            );
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' .
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ' .
            'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' .
            $items .
            '</urlset>';
    }
}
