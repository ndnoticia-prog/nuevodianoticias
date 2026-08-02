<?php

declare(strict_types=1);

namespace NDCore\Activation;

use NDCore\Application;
use NDCore\Scheduler\Scheduler;

final class Deactivator
{
    private const QUEUE_TICK_HOOK = 'nd_core/queue/tick';

    public function deactivate(bool $networkWide): void
    {
        $app = Application::getInstance();
        $app->boot();

        $app->make(Scheduler::class)->unschedule(self::QUEUE_TICK_HOOK);
    }
}
