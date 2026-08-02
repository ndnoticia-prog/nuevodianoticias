<?php

declare(strict_types=1);

namespace NDSeo\Tests\Unit\Meta;

use NDCore\Config\Config;
use NDSeo\Context\SeoContext;
use NDSeo\Meta\TwitterCardBuilder;
use PHPUnit\Framework\TestCase;

final class TwitterCardBuilderTest extends TestCase
{
    public function test_uses_large_image_card_when_image_is_present(): void
    {
        $context = new SeoContext(
            title: 'Titular',
            description: 'Descripción',
            canonicalUrl: 'https://example.test/articulo/',
            imageUrl: 'https://example.test/imagen.jpg',
            type: 'article',
            isSingular: true,
            noindex: false,
        );

        $tags = (new TwitterCardBuilder(new Config()))->build($context);

        self::assertSame('summary_large_image', $tags['twitter:card']);
        self::assertSame('Titular', $tags['twitter:title']);
        self::assertSame('Descripción', $tags['twitter:description']);
        self::assertSame('https://example.test/imagen.jpg', $tags['twitter:image']);
        self::assertArrayNotHasKey('twitter:site', $tags);
    }

    public function test_falls_back_to_summary_card_without_image(): void
    {
        $context = new SeoContext(
            title: 'Titular',
            description: '',
            canonicalUrl: 'https://example.test/',
            imageUrl: null,
            type: 'website',
            isSingular: false,
            noindex: false,
        );

        $tags = (new TwitterCardBuilder(new Config()))->build($context);

        self::assertSame('summary', $tags['twitter:card']);
        self::assertArrayNotHasKey('twitter:description', $tags);
        self::assertArrayNotHasKey('twitter:image', $tags);
    }

    public function test_includes_twitter_site_when_configured(): void
    {
        $config = new Config(['seo' => ['social' => ['twitter_site' => '@ndnoticia']]]);
        $context = new SeoContext(
            title: 'Titular',
            description: '',
            canonicalUrl: 'https://example.test/',
            imageUrl: null,
            type: 'website',
            isSingular: false,
            noindex: false,
        );

        $tags = (new TwitterCardBuilder($config))->build($context);

        self::assertSame('@ndnoticia', $tags['twitter:site']);
    }
}
