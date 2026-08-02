<?php

declare(strict_types=1);

namespace NDBuilder\Tests\Unit;

use NDBuilder\Block;
use NDBuilder\BlockRegistry;
use NDBuilder\Contracts\BlockRenderer;
use NDBuilder\Renderer;
use PHPUnit\Framework\TestCase;

final class RendererTestUppercaseRenderer implements BlockRenderer
{
    public function render(Block $block): string
    {
        return strtoupper((string) $block->attribute('text', ''));
    }
}

final class RendererTest extends TestCase
{
    public function test_render_delegates_to_the_registered_renderer(): void
    {
        $registry = new BlockRegistry();
        $registry->register('hero', new RendererTestUppercaseRenderer());

        $renderer = new Renderer($registry);
        $html = $renderer->render(new Block('hero', 'hero-1', ['text' => 'titular']));

        self::assertSame('TITULAR', $html);
    }

    public function test_render_returns_empty_string_for_unregistered_type(): void
    {
        $renderer = new Renderer(new BlockRegistry());

        self::assertSame('', $renderer->render(new Block('unknown', 'x-1')));
    }

    public function test_render_many_concatenates_in_order(): void
    {
        $registry = new BlockRegistry();
        $registry->register('hero', new RendererTestUppercaseRenderer());

        $renderer = new Renderer($registry);

        $html = $renderer->renderMany([
            new Block('hero', 'a', ['text' => 'uno']),
            new Block('hero', 'b', ['text' => 'dos']),
        ]);

        self::assertSame('UNODOS', $html);
    }
}
