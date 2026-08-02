<?php

declare(strict_types=1);

namespace NDBuilder\Events;

use NDBuilder\Block;
use NDCore\Events\Event;

/**
 * Evento interno (no un hook de WordPress) despachado cada vez que
 * {@see \NDBuilder\Renderer} produce HTML no vacío para un bloque. Permite a
 * otros paquetes (p. ej. nd-analytics, para registrar impresiones) escuchar
 * qué contenido se mostró sin acoplar nd-builder a ellos.
 */
final class BlockRendered extends Event
{
    public function __construct(
        public readonly Block $block,
        public readonly string $html,
    ) {
    }
}
