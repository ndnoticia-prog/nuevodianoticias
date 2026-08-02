<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Hooks;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use NDCore\Hooks\HookManager;
use NDCore\Tests\BrainMonkeyTestCase;

final class HookManagerTest extends BrainMonkeyTestCase
{
    public function test_add_action_registers_with_wordpress_and_remembers_handle(): void
    {
        $manager = new HookManager();
        $callback = static function (): void {
        };

        Actions\expectAdded('nd_core/booted')->once()->with('nd_core/booted', $callback, 10, 1);

        $handle = $manager->addAction('nd_core/booted', $callback);

        self::assertSame('action', $handle->type);
        self::assertSame('nd_core/booted', $handle->hookName);
        self::assertCount(1, $manager->registered());
    }

    public function test_add_filter_registers_with_wordpress(): void
    {
        $manager = new HookManager();
        $callback = static fn (array $value): array => $value;

        Filters\expectAdded('nd_core/providers')->once()->with('nd_core/providers', $callback, 20, 1);

        $handle = $manager->addFilter('nd_core/providers', $callback, 20);

        self::assertSame('filter', $handle->type);
    }

    public function test_do_action_and_apply_filters_delegate_to_wordpress(): void
    {
        $manager = new HookManager();

        Actions\expectDone('nd_core/booted')->once()->with('payload');
        $manager->doAction('nd_core/booted', 'payload');

        Filters\expectApplied('nd_core/providers')->once()->with(['a'], 'extra')->andReturn(['a', 'b']);
        $result = $manager->applyFilters('nd_core/providers', ['a'], 'extra');

        self::assertSame(['a', 'b'], $result);
    }

    public function test_remove_forgets_the_handle(): void
    {
        $manager = new HookManager();
        $callback = static function (): void {
        };

        Actions\expectAdded('nd_core/booted')->once()->andReturn(true);
        $handle = $manager->addAction('nd_core/booted', $callback);

        Actions\expectRemoved('nd_core/booted')->once()->andReturn(true);

        $manager->remove($handle);

        self::assertCount(0, $manager->registered());
    }
}
