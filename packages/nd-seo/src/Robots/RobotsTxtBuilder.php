<?php

declare(strict_types=1);

namespace NDSeo\Robots;

/**
 * Añade directivas `Sitemap:` a `robots.txt` (filtro `robots_txt` de
 * WordPress core), apuntando tanto al sitemap general de core
 * (`wp-sitemap.xml`) como al de Google News de nd-seo.
 */
final class RobotsTxtBuilder
{
    public function filter(string $output, bool $public): string
    {
        if (! $public) {
            return $output;
        }

        $lines = [
            'Sitemap: ' . home_url('/wp-sitemap.xml'),
            'Sitemap: ' . home_url('/sitemap-news.xml'),
        ];

        return rtrim($output) . "\n\n" . implode("\n", $lines) . "\n";
    }
}
