<?php

declare(strict_types=1);

namespace NDBuilder\Tests\Unit;

use InvalidArgumentException;
use NDBuilder\Block;
use NDBuilder\BlockRegistry;
use NDBuilder\Contracts\BlockRenderer;
use PHPUnit\Framework\TestCase;

final class BlockRegistryTestRenderer implements BlockRenderer
{
    public function render(Block $block): string
    {
        return 'rendered:' . $block->type;
    }
}

final class BlockRegistryTest extends TestCase
{
    public function test_register_and_has(): void
    {
        $registry = new BlockRegistry();

        self::assertFalse($registry->has('hero'));

        $registry->register('hero', new BlockRegistryTestRenderer());

        self::assertTrue($registry->has('hero'));
        self::assertSame(['hero'], $registry->registeredTypes());
    }

    public function test_renderer_for_returns_the_registered_renderer(): void
    {
        $registry = new BlockRegistry();
        $renderer = new BlockRegistryTestRenderer();

        $registry->register('hero', $renderer);

        self::assertSame($renderer, $registry->rendererFor('hero'));
    }

    public function test_renderer_for_unknown_type_throws(): void
    {
        $registry = new BlockRegistry();

        $this->expectException(InvalidArgumentException::class);

        $registry->rendererFor('unknown');
    }
}
