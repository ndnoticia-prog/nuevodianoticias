<?php

declare(strict_types=1);

namespace NDCore\Activation;

use NDCore\Application;
use NDCore\Installer\Installer;

final class Activator
{
    public function activate(bool $networkWide): void
    {
        $app = Application::getInstance();
        $app->boot();

        $app->make(Installer::class)->install();
    }
}
