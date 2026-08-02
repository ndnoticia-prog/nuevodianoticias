<?php

/**
 * Bootstrap de PHPUnit para nd-ai.
 *
 * @package NDAi
 */

declare(strict_types=1);

$autoloader = dirname(__DIR__) . '/vendor/autoload.php';

if (! file_exists($autoloader)) {
    fwrite(STDERR, "Faltan las dependencias de Composer. Ejecuta \"composer install\" en packages/nd-ai.\n");
    exit(1);
}

require_once $autoloader;
