<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Support;

use NDCore\Support\Collection;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    public function test_map_filter_values(): void
    {
        $collection = Collection::make([1, 2, 3, 4]);

        $result = $collection
            ->map(static fn (int $value): int => $value * 2)
            ->filter(static fn (int $value): bool => $value > 4)
            ->values()
            ->toArray();

        self::assertSame([6, 8], $result);
    }

    public function test_reduce(): void
    {
        $sum = Collection::make([1, 2, 3])->reduce(
            static fn (int $carry, int $item): int => $carry + $item,
            0
        );

        self::assertSame(6, $sum);
    }

    public function test_pluck_and_sum(): void
    {
        $articles = Collection::make([
            ['title' => 'A', 'views' => 10],
            ['title' => 'B', 'views' => 25],
        ]);

        self::assertSame([10, 25], $articles->pluck('views')->toArray());
        self::assertSame(35, $articles->sum('views'));
    }

    public function test_first_and_last(): void
    {
        $collection = Collection::make(['x', 'y', 'z']);

        self::assertSame('x', $collection->first());
        self::assertSame('z', $collection->last());
    }

    public function test_sort_by(): void
    {
        $articles = Collection::make([
            ['title' => 'B', 'views' => 5],
            ['title' => 'A', 'views' => 20],
        ]);

        $sorted = $articles->sortBy('views')->toArray();

        self::assertSame('B', $sorted[0]['title']);
        self::assertSame('A', $sorted[1]['title']);
    }

    public function test_array_access_and_countable(): void
    {
        $collection = new Collection(['a' => 1]);
        $collection['b'] = 2;

        self::assertCount(2, $collection);
        self::assertTrue(isset($collection['a']));
        self::assertSame(2, $collection['b']);

        unset($collection['a']);

        self::assertFalse(isset($collection['a']));
    }

    public function test_is_empty(): void
    {
        self::assertTrue(Collection::make([])->isEmpty());
        self::assertTrue(Collection::make([1])->isNotEmpty());
    }
}
