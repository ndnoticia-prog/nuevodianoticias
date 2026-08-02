<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Settings;

use Brain\Monkey\Functions;
use NDCore\Settings\SettingsRepository;
use NDCore\Tests\BrainMonkeyTestCase;

final class SettingsRepositoryTest extends BrainMonkeyTestCase
{
    public function test_get_reads_option_with_nd_prefix(): void
    {
        Functions\expect('get_option')->once()->with('nd_cache_driver', 'transient')->andReturn('redis');

        $repository = new SettingsRepository();

        self::assertSame('redis', $repository->get('cache_driver', 'transient'));
    }

    public function test_set_writes_option_with_nd_prefix(): void
    {
        Functions\expect('update_option')->once()->with('nd_cache_driver', 'redis', true)->andReturn(true);

        $repository = new SettingsRepository();

        self::assertTrue($repository->set('cache_driver', 'redis'));
    }

    public function test_forget_deletes_option_with_nd_prefix(): void
    {
        Functions\expect('delete_option')->once()->with('nd_cache_driver')->andReturn(true);

        $repository = new SettingsRepository();

        self::assertTrue($repository->forget('cache_driver'));
    }

    public function test_has_returns_false_when_option_absent(): void
    {
        Functions\when('get_option')->justReturn(null);

        $repository = new SettingsRepository();

        self::assertFalse($repository->has('missing_key'));
    }

    public function test_has_returns_true_even_when_stored_value_is_null(): void
    {
        Functions\expect('get_option')->andReturnUsing(
            static fn (string $name, mixed $default): mixed => $default instanceof \stdClass ? null : $default
        );

        $repository = new SettingsRepository();

        self::assertTrue($repository->has('explicitly_null'));
    }
}
