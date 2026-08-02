<?php

declare(strict_types=1);

namespace NDSeo\Meta;

use NDCore\Config\Config;
use NDSeo\Context\SeoContext;

final class TwitterCardBuilder
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @return array<string, string>
     */
    public function build(SeoContext $context): array
    {
        $tags = [
            'twitter:card' => $context->imageUrl !== null ? 'summary_large_image' : 'summary',
            'twitter:title' => $context->title,
        ];

        if ($context->description !== '') {
            $tags['twitter:description'] = $context->description;
        }

        if ($context->imageUrl !== null) {
            $tags['twitter:image'] = $context->imageUrl;
        }

        $site = $this->config->get('seo.social.twitter_site');

        if (is_string($site) && $site !== '') {
            $tags['twitter:site'] = $site;
        }

        return $tags;
    }
}
