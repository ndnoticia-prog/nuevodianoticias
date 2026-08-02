<?php

declare(strict_types=1);

namespace NDCore\Activation;

use NDCore\Application;
use NDCore\Installer\Installer;

final class Uninstaller
{
    public function uninstall(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $app->make(Installer::class)->uninstall();
    }
}
