<?php

declare(strict_types=1);

namespace NDMedia\Tests\Unit\Providers;

use NDCore\Config\Config;
use NDCore\Container\Container;
use NDMedia\Cdn\CdnUrlRewriter;
use NDMedia\Optimization\ModernFormatConverter;
use NDMedia\Podcast\PodcastFeedEnhancer;
use NDMedia\Providers\MediaServiceProvider;
use PHPUnit\Framework\TestCase;

final class MediaServiceProviderTest extends TestCase
{
    public function test_register_binds_all_services_and_loads_config(): void
    {
        $container = new Container();
        $config = new Config();
        $container->instance(Config::class, $config);

        (new MediaServiceProvider($container))->register();

        self::assertInstanceOf(ModernFormatConverter::class, $container->make(ModernFormatConverter::class));
        self::assertInstanceOf(CdnUrlRewriter::class, $container->make(CdnUrlRewriter::class));
        self::assertInstanceOf(PodcastFeedEnhancer::class, $container->make(PodcastFeedEnhancer::class));
        self::assertSame('webp', $config->get('media.modern_format'));
    }
}
