<?php

declare(strict_types=1);

namespace NDCore\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

/**
 * Caso de prueba base para clases que dependen de funciones nativas de
 * WordPress, simuladas con Brain Monkey en lugar de un WordPress real.
 */
abstract class BrainMonkeyTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
