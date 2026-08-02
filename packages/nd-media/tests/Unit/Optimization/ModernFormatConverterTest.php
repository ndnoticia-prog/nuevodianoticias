<?php

declare(strict_types=1);

namespace NDMedia\Tests\Unit\Optimization;

use Brain\Monkey\Functions;
use NDCore\Config\Config;
use NDMedia\Optimization\ModernFormatConverter;
use NDMedia\Tests\BrainMonkeyTestCase;

final class ModernFormatConverterTest extends BrainMonkeyTestCase
{
    public function test_converts_to_webp_when_configured_and_supported(): void
    {
        $config = new Config(['media' => ['modern_format' => 'webp']]);
        Functions\expect('function_exists')->with('imagewebp')->andReturn(true);

        $result = (new ModernFormatConverter($config))->filterOutputFormat(['image/jpeg' => 'image/jpeg']);

        self::assertSame('image/webp', $result['image/jpeg']);
        self::assertSame('image/webp', $result['image/png']);
    }

    public function test_leaves_formats_untouched_when_webp_unsupported(): void
    {
        $config = new Config(['media' => ['modern_format' => 'webp']]);
        Functions\expect('function_exists')->with('imagewebp')->andReturn(false);

        $result = (new ModernFormatConverter($config))->filterOutputFormat(['image/jpeg' => 'image/jpeg']);

        self::assertSame(['image/jpeg' => 'image/jpeg'], $result);
    }

    public function test_avif_degrades_to_webp_when_avif_unsupported(): void
    {
        $config = new Config(['media' => ['modern_format' => 'avif']]);
        Functions\expect('function_exists')->with('imageavif')->andReturn(false);
        Functions\expect('function_exists')->with('imagewebp')->andReturn(true);

        $result = (new ModernFormatConverter($config))->filterOutputFormat(['image/jpeg' => 'image/jpeg']);

        self::assertSame('image/webp', $result['image/jpeg']);
    }

    public function test_avif_used_when_supported(): void
    {
        $config = new Config(['media' => ['modern_format' => 'avif']]);
        Functions\expect('function_exists')->with('imageavif')->andReturn(true);

        $result = (new ModernFormatConverter($config))->filterOutputFormat(['image/jpeg' => 'image/jpeg']);

        self::assertSame('image/avif', $result['image/jpeg']);
    }

    public function test_disabled_when_config_is_null(): void
    {
        $config = new Config(['media' => ['modern_format' => null]]);

        $result = (new ModernFormatConverter($config))->filterOutputFormat(['image/jpeg' => 'image/jpeg']);

        self::assertSame(['image/jpeg' => 'image/jpeg'], $result);
    }
}
