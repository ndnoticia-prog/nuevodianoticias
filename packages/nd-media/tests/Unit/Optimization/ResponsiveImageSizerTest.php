<?php

declare(strict_types=1);

namespace NDMedia\Tests\Unit\Optimization;

use NDMedia\Optimization\ResponsiveImageSizer;
use PHPUnit\Framework\TestCase;

final class ResponsiveImageSizerTest extends TestCase
{
    public function test_returns_sizes_tuned_to_theme_breakpoints(): void
    {
        $sizer = new ResponsiveImageSizer();

        self::assertSame(
            '(max-width: 480px) 100vw, (max-width: 768px) 50vw, (max-width: 1024px) 33vw, 25vw',
            $sizer->filterSizes('(max-width: 600px) 480px, 800px')
        );
    }
}
