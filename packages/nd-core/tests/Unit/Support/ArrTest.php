<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Support;

use NDCore\Support\Arr;
use PHPUnit\Framework\TestCase;

final class ArrTest extends TestCase
{
    public function test_get_supports_dot_notation(): void
    {
        $array = ['cache' => ['redis' => ['host' => '127.0.0.1']]];

        self::assertSame('127.0.0.1', Arr::get($array, 'cache.redis.host'));
        self::assertNull(Arr::get($array, 'cache.redis.port'));
        self::assertSame('fallback', Arr::get($array, 'cache.redis.port', 'fallback'));
    }

    public function test_set_creates_nested_structure(): void
    {
        $array = Arr::set([], 'cache.redis.host', '127.0.0.1');

        self::assertSame(['cache' => ['redis' => ['host' => '127.0.0.1']]], $array);
    }

    public function test_has(): void
    {
        $array = ['a' => ['b' => null]];

        self::assertTrue(Arr::has($array, 'a.b'));
        self::assertFalse(Arr::has($array, 'a.c'));
    }

    public function test_only_and_except(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        self::assertSame(['a' => 1, 'c' => 3], Arr::only($array, ['a', 'c']));
        self::assertSame(['b' => 2], Arr::except($array, ['a', 'c']));
    }

    public function test_flatten(): void
    {
        $array = ['a' => [1, 2, ['b' => 3]]];

        self::assertSame([1, 2, 3], Arr::flatten($array));
    }
}
