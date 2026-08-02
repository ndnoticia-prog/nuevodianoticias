<?php

declare(strict_types=1);

namespace NDSeo\Schema;

use NDSeo\Context\SeoContext;
use NDSeo\Schema\Contracts\SchemaProvider;

/**
 * `NewsArticle` en lugar de `Article`: es el tipo que Google recomienda
 * para elegibilidad en Google News/Discover en sitios editoriales.
 */
final class NewsArticleSchema implements SchemaProvider
{
    public function supports(SeoContext $context): bool
    {
        return $context->isSingular && $context->post !== null;
    }

    public function build(SeoContext $context): array
    {
        $post = $context->post;

        $schema = [
            '@type' => 'NewsArticle',
            '@id' => $context->canonicalUrl . '#article',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $context->canonicalUrl,
            ],
            'headline' => wp_strip_all_tags(get_the_title($post)),
            'datePublished' => get_the_date(DATE_W3C, $post),
            'dateModified' => get_the_modified_date(DATE_W3C, $post),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', (int) $post->post_author),
            ],
            'publisher' => ['@id' => home_url('/#organization')],
        ];

        if ($context->description !== '') {
            $schema['description'] = $context->description;
        }

        if ($context->imageUrl !== null) {
            $schema['image'] = [$context->imageUrl];
        }

        return $schema;
    }
}
