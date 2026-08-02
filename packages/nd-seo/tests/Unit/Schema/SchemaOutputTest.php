<?php

declare(strict_types=1);

namespace NDSeo\Tests\Unit\Schema;

use Brain\Monkey\Functions;
use NDCore\Config\Config;
use NDSeo\Context\SeoContext;
use NDSeo\Context\SeoContextResolver;
use NDSeo\Schema\Contracts\SchemaProvider;
use NDSeo\Schema\SchemaOutput;
use NDSeo\Tests\BrainMonkeyTestCase;

final class SchemaOutputTestAlwaysProvider implements SchemaProvider
{
    public function supports(SeoContext $context): bool
    {
        return true;
    }

    public function build(SeoContext $context): array
    {
        return ['@type' => 'Thing', 'name' => 'Uno'];
    }
}

final class SchemaOutputTestNeverProvider implements SchemaProvider
{
    public function supports(SeoContext $context): bool
    {
        return false;
    }

    public function build(SeoContext $context): array
    {
        return ['@type' => 'Thing', 'name' => 'Nunca'];
    }
}

final class SchemaOutputTest extends BrainMonkeyTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // La página resuelta (home) es irrelevante para este test: lo que se
        // comprueba es que SchemaOutput solo agrega los providers cuyo
        // supports() es true.
        Functions\when('is_singular')->justReturn(false);
        Functions\when('is_404')->justReturn(false);
        Functions\when('is_home')->justReturn(true);
        Functions\when('is_front_page')->justReturn(true);
        Functions\when('get_bloginfo')->justReturn('');
        Functions\when('home_url')->justReturn('https://example.test/');
        Functions\when('wp_json_encode')->alias('json_encode');
    }

    public function test_renders_only_the_output_of_supported_providers(): void
    {
        $resolver = new SeoContextResolver(new Config());
        $output = new SchemaOutput($resolver, [
            new SchemaOutputTestAlwaysProvider(),
            new SchemaOutputTestNeverProvider(),
        ]);

        ob_start();
        $output->render();
        $html = ob_get_clean();

        self::assertStringStartsWith('<script type="application/ld+json">', $html);
        self::assertStringContainsString('"@context":"https://schema.org"', $html);
        self::assertStringContainsString('"name":"Uno"', $html);
        self::assertStringNotContainsString('Nunca', $html);
    }

    public function test_renders_nothing_when_no_provider_supports_the_context(): void
    {
        $resolver = new SeoContextResolver(new Config());
        $output = new SchemaOutput($resolver, [new SchemaOutputTestNeverProvider()]);

        ob_start();
        $output->render();
        $html = ob_get_clean();

        self::assertSame('', $html);
    }
}
