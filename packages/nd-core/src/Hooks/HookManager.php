<?php

declare(strict_types=1);

namespace NDCore\Hooks;

/**
 * Única puerta de entrada de ND Platform hacia el sistema de hooks de
 * WordPress. Ningún paquete debe llamar a `add_action`/`add_filter`
 * directamente: deben depender de esta clase para seguir siendo comprobables
 * unitariamente sin un WordPress real.
 */
final class HookManager
{
    /**
     * @var list<HookHandle>
     */
    private array $handles = [];

    public function addAction(
        string $hookName,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): HookHandle {
        add_action($hookName, $callback, $priority, $acceptedArgs);

        return $this->remember('action', $hookName, $callback, $priority, $acceptedArgs);
    }

    public function addFilter(
        string $hookName,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): HookHandle {
        add_filter($hookName, $callback, $priority, $acceptedArgs);

        return $this->remember('filter', $hookName, $callback, $priority, $acceptedArgs);
    }

    public function remove(HookHandle $handle): bool
    {
        $removed = $handle->type === 'action'
            ? remove_action($handle->hookName, $handle->callback, $handle->priority)
            : remove_filter($handle->hookName, $handle->callback, $handle->priority);

        $this->handles = array_values(array_filter(
            $this->handles,
            static fn (HookHandle $existing): bool => $existing !== $handle
        ));

        return $removed;
    }

    /**
     * @param mixed ...$args
     */
    public function doAction(string $hookName, mixed ...$args): void
    {
        do_action($hookName, ...$args);
    }

    /**
     * @param mixed ...$args
     */
    public function applyFilters(string $hookName, mixed $value, mixed ...$args): mixed
    {
        return apply_filters($hookName, $value, ...$args);
    }

    /**
     * @return list<HookHandle>
     */
    public function registered(): array
    {
        return $this->handles;
    }

    private function remember(
        string $type,
        string $hookName,
        callable $callback,
        int $priority,
        int $acceptedArgs
    ): HookHandle {
        $handle = new HookHandle($type, $hookName, $callback, $priority, $acceptedArgs);
        $this->handles[] = $handle;

        return $handle;
    }
}
