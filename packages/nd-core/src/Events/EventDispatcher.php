<?php

declare(strict_types=1);

namespace NDCore\Events;

use Closure;
use NDCore\Container\Container;

/**
 * Bus de eventos interno de ND Platform, independiente del sistema de hooks
 * de WordPress. Usado para comunicación desacoplada entre paquetes.
 */
final class EventDispatcher
{
    /**
     * @var array<class-string<Event>, list<array{listener: Listener|Closure|class-string<Listener>, priority: int}>>
     */
    private array $listeners = [];

    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param class-string<Event> $eventClass
     */
    public function listen(string $eventClass, Listener|Closure|string $listener, int $priority = 10): void
    {
        $this->listeners[$eventClass][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];
    }

    public function dispatch(Event $event): Event
    {
        $listeners = $this->listeners[$event::class] ?? [];

        usort($listeners, static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        foreach ($listeners as $entry) {
            if ($event->isPropagationStopped()) {
                break;
            }

            $this->invoke($entry['listener'], $event);
        }

        return $event;
    }

    /**
     * @param class-string<Event> $eventClass
     */
    public function hasListeners(string $eventClass): bool
    {
        return ! empty($this->listeners[$eventClass]);
    }

    /**
     * @param Listener|Closure|class-string<Listener> $listener
     */
    private function invoke(Listener|Closure|string $listener, Event $event): void
    {
        if ($listener instanceof Closure) {
            $listener($event);

            return;
        }

        $instance = $listener instanceof Listener
            ? $listener
            : $this->container->make($listener);

        $instance->handle($event);
    }
}
