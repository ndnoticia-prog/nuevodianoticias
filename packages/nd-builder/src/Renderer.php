<?php

declare(strict_types=1);

namespace NDBuilder;

/**
 * Motor de renderizado server-side del constructor visual: traduce una
 * lista de {@see Block} a HTML delegando en el {@see BlockRegistry}.
 */
final class Renderer
{
    public function __construct(private readonly BlockRegistry $registry)
    {
    }

    public function render(Block $block): string
    {
        if (! $this->registry->has($block->type)) {
            return '';
        }

        return $this->registry->rendererFor($block->type)->render($block);
    }

    /**
     * @param list<Block> $blocks
     */
    public function renderMany(array $blocks): string
    {
        return implode('', array_map($this->render(...), $blocks));
    }
}
