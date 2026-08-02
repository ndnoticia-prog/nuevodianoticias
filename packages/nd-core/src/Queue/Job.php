<?php

declare(strict_types=1);

namespace NDCore\Queue;

use NDCore\Queue\Contracts\ShouldQueue;

/**
 * Clase base conveniente para trabajos encolados. Las subclases deben tener
 * un constructor sin argumentos obligatorios: el estado se restaura a través
 * de propiedades públicas serializadas en {@see toPayload()}.
 */
abstract class Job implements ShouldQueue
{
    public int $maxAttempts = 3;

    abstract public function handle(): void;

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): static
    {
        $instance = new static();

        foreach ($payload as $property => $value) {
            if (property_exists($instance, $property)) {
                $instance->{$property} = $value;
            }
        }

        return $instance;
    }
}
