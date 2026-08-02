<?php

declare(strict_types=1);

namespace NDCore\Queue\Contracts;

interface ShouldQueue
{
    public function handle(): void;

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array;

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): static;
}
