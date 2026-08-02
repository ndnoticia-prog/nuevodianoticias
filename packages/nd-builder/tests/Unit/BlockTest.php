<?php

declare(strict_types=1);

namespace NDBuilder\Tests\Unit;

use NDBuilder\Block;
use PHPUnit\Framework\TestCase;

final class BlockTest extends TestCase
{
    public function test_attribute_returns_default_when_missing(): void
    {
        $block = new Block('hero', 'hero-1', ['title' => 'Titular']);

        self::assertSame('Titular', $block->attribute('title'));
        self::assertNull($block->attribute('subtitle'));
        self::assertSame('fallback', $block->attribute('subtitle', 'fallback'));
    }

    public function test_with_attributes_merges_without_mutating_original(): void
    {
        $original = new Block('hero', 'hero-1', ['title' => 'Titular']);
        $modified = $original->withAttributes(['subtitle' => 'Bajada']);

        self::assertSame(['title' => 'Titular'], $original->attributes);
        self::assertSame(['title' => 'Titular', 'subtitle' => 'Bajada'], $modified->attributes);
        self::assertNotSame($original, $modified);
    }
}
