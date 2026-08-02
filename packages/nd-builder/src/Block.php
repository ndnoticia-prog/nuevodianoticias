<?php

declare(strict_types=1);

namespace NDBuilder;

/**
 * Unidad de datos inmutable de un bloque del constructor visual: qué tipo
 * es, un identificador único y sus atributos. No sabe renderizarse a sí
 * mismo: eso es responsabilidad de un {@see Contracts\BlockRenderer}.
 */
final class Block
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly array $attributes = [],
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function withAttributes(array $attributes): self
    {
        return new self($this->type, $this->id, array_merge($this->attributes, $attributes));
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
