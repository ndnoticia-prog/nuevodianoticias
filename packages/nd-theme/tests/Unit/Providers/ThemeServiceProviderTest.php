<?php

declare(strict_types=1);

namespace NDTheme\Tests\Unit\Providers;

use Brain\Monkey\Functions;
use NDCore\Container\Container;
use NDTheme\Content\HomeContentProvider;
use NDTheme\Providers\ThemeServiceProvider;
use NDTheme\Tests\BrainMonkeyTestCase;

final class ThemeServiceProviderTest extends BrainMonkeyTestCase
{
    public function test_register_binds_home_content_provider_as_singleton(): void
    {
        $container = new Container();
        $provider = new ThemeServiceProvider($container);

        $provider->register();

        $first = $container->make(HomeContentProvider::class);
        $second = $container->make(HomeContentProvider::class);

        self::assertSame($first, $second);
    }

    public function test_setup_theme_registers_theme_supports_and_menus(): void
    {
        $container = new Container();
        $provider = new ThemeServiceProvider($container);

        Functions\expect('add_theme_support')->times(5);
        Functions\expect('register_nav_menus')->once()->with([
            'primary' => 'Menú principal',
            'footer' => 'Menú de pie de página',
        ]);
        Functions\when('__')->returnArg(1);

        $provider->setupTheme();

        $this->addToAssertionCount(1);
    }

    public function test_enqueue_assets_does_nothing_when_build_output_is_missing(): void
    {
        $container = new Container();
        $provider = new ThemeServiceProvider($container);

        Functions\expect('file_exists')->twice()->andReturn(false);
        Functions\expect('wp_enqueue_style')->never();
        Functions\expect('wp_enqueue_script')->never();

        $provider->enqueueAssets();

        $this->addToAssertionCount(1);
    }

    public function test_enqueue_assets_enqueues_built_css_and_js_when_present(): void
    {
        $container = new Container();
        $provider = new ThemeServiceProvider($container);

        Functions\expect('file_exists')->twice()->andReturn(true);
        Functions\expect('filemtime')->twice()->andReturn(1700000000);

        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('nd-theme', ND_THEME_URI . '/dist/app.css', [], '1700000000');

        Functions\expect('wp_enqueue_script')
            ->once()
            ->with('nd-theme', ND_THEME_URI . '/dist/app.js', [], '1700000000', true);

        $provider->enqueueAssets();
    }
}
