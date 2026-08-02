<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Container;

use NDCore\Container\Container;
use NDCore\Container\Exceptions\ContainerException;
use NDCore\Container\Exceptions\UnresolvableParameterException;
use PHPUnit\Framework\TestCase;

interface ContainerTestLoggerInterface
{
    public function log(string $message): string;
}

final class ContainerTestArrayLogger implements ContainerTestLoggerInterface
{
    public function log(string $message): string
    {
        return $message;
    }
}

final class ContainerTestService
{
    public function __construct(public readonly ContainerTestLoggerInterface $logger)
    {
    }
}

final class ContainerTestUnresolvable
{
    public function __construct(public readonly string $missing)
    {
    }
}

final class ContainerTestCircularA
{
    public function __construct(public readonly ContainerTestCircularB $b)
    {
    }
}

final class ContainerTestCircularB
{
    public function __construct(public readonly ContainerTestCircularA $a)
    {
    }
}

final class ContainerTestOptionalDependency
{
    public function __construct(public readonly ?ContainerTestLoggerInterface $logger = null)
    {
    }
}

final class ContainerTest extends TestCase
{
    public function test_bind_resolves_via_closure(): void
    {
        $container = new Container();
        $container->bind('answer', static fn (): int => 42);

        self::assertSame(42, $container->make('answer'));
    }

    public function test_singleton_returns_same_instance(): void
    {
        $container = new Container();
        $container->singleton(ContainerTestArrayLogger::class);

        $first = $container->make(ContainerTestArrayLogger::class);
        $second = $container->make(ContainerTestArrayLogger::class);

        self::assertSame($first, $second);
    }

    public function test_bind_without_shared_creates_new_instances(): void
    {
        $container = new Container();
        $container->bind(ContainerTestArrayLogger::class);

        $first = $container->make(ContainerTestArrayLogger::class);
        $second = $container->make(ContainerTestArrayLogger::class);

        self::assertNotSame($first, $second);
    }

    public function test_instance_registers_prebuilt_object(): void
    {
        $container = new Container();
        $logger = new ContainerTestArrayLogger();

        $container->instance(ContainerTestLoggerInterface::class, $logger);

        self::assertSame($logger, $container->get(ContainerTestLoggerInterface::class));
    }

    public function test_autowiring_resolves_bound_interface_dependency(): void
    {
        $container = new Container();
        $container->bind(ContainerTestLoggerInterface::class, ContainerTestArrayLogger::class);

        $service = $container->make(ContainerTestService::class);

        self::assertInstanceOf(ContainerTestService::class, $service);
        self::assertInstanceOf(ContainerTestArrayLogger::class, $service->logger);
    }

    public function test_has_reflects_bindings_and_instances(): void
    {
        $container = new Container();

        self::assertFalse($container->has('nonexistent-abstract'));

        $container->bind('nonexistent-abstract', static fn (): int => 1);

        self::assertTrue($container->has('nonexistent-abstract'));
    }

    public function test_unresolvable_scalar_parameter_throws(): void
    {
        $container = new Container();

        $this->expectException(UnresolvableParameterException::class);

        $container->make(ContainerTestUnresolvable::class);
    }

    public function test_circular_dependency_throws_container_exception(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);

        $container->make(ContainerTestCircularA::class);
    }

    public function test_optional_class_dependency_falls_back_to_default(): void
    {
        $container = new Container();

        $resolved = $container->make(ContainerTestOptionalDependency::class);

        self::assertNull($resolved->logger);
    }
}
