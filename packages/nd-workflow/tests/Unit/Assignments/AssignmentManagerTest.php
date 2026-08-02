<?php

declare(strict_types=1);

namespace NDWorkflow\Tests\Unit\Assignments;

use Brain\Monkey\Functions;
use NDWorkflow\Assignments\AssignmentManager;
use NDWorkflow\Tests\BrainMonkeyTestCase;

final class AssignmentManagerTest extends BrainMonkeyTestCase
{
    public function test_assign_updates_post_meta(): void
    {
        Functions\expect('update_post_meta')->once()->with(42, '_nd_assigned_to', 7)->andReturn(true);

        self::assertTrue((new AssignmentManager())->assign(42, 7));
    }

    public function test_unassign_deletes_post_meta(): void
    {
        Functions\expect('delete_post_meta')->once()->with(42, '_nd_assigned_to')->andReturn(true);

        self::assertTrue((new AssignmentManager())->unassign(42));
    }

    public function test_assigned_to_returns_null_when_not_set(): void
    {
        Functions\expect('get_post_meta')->once()->with(42, '_nd_assigned_to', true)->andReturn('');

        self::assertNull((new AssignmentManager())->assignedTo(42));
    }

    public function test_assigned_to_returns_user_id_when_set(): void
    {
        Functions\expect('get_post_meta')->once()->with(42, '_nd_assigned_to', true)->andReturn('7');

        self::assertSame(7, (new AssignmentManager())->assignedTo(42));
    }
}
