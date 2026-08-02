<?php

declare(strict_types=1);

namespace NDSeo\Tests\Unit\Schema;

use Brain\Monkey\Functions;
use NDCore\Config\Config;
use NDSeo\Context\SeoContext;
use NDSeo\Schema\OrganizationSchema;
use NDSeo\Tests\BrainMonkeyTestCase;

final class OrganizationSchemaTest extends BrainMonkeyTestCase
{
    private function context(): SeoContext
    {
        return new SeoContext(
            title: 'Titular',
            description: '',
            canonicalUrl: 'https://example.test/',
            imageUrl: null,
            type: 'website',
            isSingular: false,
            noindex: false,
        );
    }

    public function test_supports_every_context(): void
    {
        self::assertTrue((new OrganizationSchema(new Config()))->supports($this->context()));
    }

    public function test_falls_back_to_bloginfo_name_without_configured_organization(): void
    {
        Functions\expect('home_url')->with('/#organization')->andReturn('https://example.test/#organization');
        Functions\expect('home_url')->with('/')->andReturn('https://example.test/');
        Functions\expect('get_bloginfo')->with('name')->andReturn('Nuevo Diario');

        $schema = (new OrganizationSchema(new Config()))->build($this->context());

        self::assertSame('Organization', $schema['@type']);
        self::assertSame('Nuevo Diario', $schema['name']);
        self::assertArrayNotHasKey('logo', $schema);
        self::assertArrayNotHasKey('sameAs', $schema);
    }

    public function test_uses_configured_organization_name_logo_and_same_as(): void
    {
        Functions\expect('home_url')->with('/#organization')->andReturn('https://example.test/#organization');
        Functions\expect('home_url')->with('/')->andReturn('https://example.test/');

        $config = new Config([
            'seo' => [
                'organization' => [
                    'name' => 'ND Noticias',
                    'logo' => 'https://example.test/logo.png',
                    'same_as' => ['https://twitter.com/ndnoticia', 'https://facebook.com/ndnoticia'],
                ],
            ],
        ]);

        $schema = (new OrganizationSchema($config))->build($this->context());

        self::assertSame('ND Noticias', $schema['name']);
        self::assertSame(['@type' => 'ImageObject', 'url' => 'https://example.test/logo.png'], $schema['logo']);
        self::assertSame(['https://twitter.com/ndnoticia', 'https://facebook.com/ndnoticia'], $schema['sameAs']);
    }
}
