<?php

declare(strict_types=1);

namespace NDBuilder\Contracts;

use NDBuilder\Block;

interface BlockRenderer
{
    public function render(Block $block): string;
}
