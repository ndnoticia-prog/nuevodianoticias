<?php

declare(strict_types=1);

return [
    'organization' => [
        'name' => defined('ND_SEO_ORG_NAME') ? ND_SEO_ORG_NAME : null,
        'logo' => defined('ND_SEO_ORG_LOGO') ? ND_SEO_ORG_LOGO : null,
        'same_as' => defined('ND_SEO_ORG_SAME_AS') && is_array(ND_SEO_ORG_SAME_AS) ? ND_SEO_ORG_SAME_AS : [],
    ],

    'social' => [
        'twitter_site' => defined('ND_SEO_TWITTER_SITE') ? ND_SEO_TWITTER_SITE : null,
        'default_image' => defined('ND_SEO_DEFAULT_IMAGE') ? ND_SEO_DEFAULT_IMAGE : null,
    ],

    'news_sitemap' => [
        'enabled' => true,
        'max_age_hours' => 48,
        'max_items' => 1000,
        'publication_name' => defined('ND_SEO_NEWS_PUBLICATION_NAME') ? ND_SEO_NEWS_PUBLICATION_NAME : null,
        'publication_language' => defined('ND_SEO_NEWS_PUBLICATION_LANGUAGE') ? ND_SEO_NEWS_PUBLICATION_LANGUAGE : 'es',
    ],
];
