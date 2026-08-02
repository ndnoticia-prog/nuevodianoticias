<?php

declare(strict_types=1);

namespace NDSeo\Meta;

use NDSeo\Context\SeoContext;

final class OpenGraphBuilder
{
    /**
     * @return array<string, string>
     */
    public function build(SeoContext $context): array
    {
        $tags = [
            'og:type' => $context->type === 'article' ? 'article' : 'website',
            'og:title' => $context->title,
            'og:site_name' => (string) get_bloginfo('name'),
            'og:locale' => (string) get_locale(),
        ];

        if ($context->canonicalUrl !== '') {
            $tags['og:url'] = $context->canonicalUrl;
        }

        if ($context->description !== '') {
            $tags['og:description'] = $context->description;
        }

        if ($context->imageUrl !== null) {
            $tags['og:image'] = $context->imageUrl;
        }

        if ($context->type === 'article' && $context->post !== null) {
            $tags['article:published_time'] = get_the_date(DATE_W3C, $context->post);

            $modified = get_the_modified_date(DATE_W3C, $context->post);

            if ($modified !== '') {
                $tags['article:modified_time'] = $modified;
            }

            foreach (get_the_category($context->post) as $category) {
                $tags['article:section'] = $category->name;

                break;
            }
        }

        return $tags;
    }
}
