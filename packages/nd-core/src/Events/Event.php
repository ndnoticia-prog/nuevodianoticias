<?php

declare(strict_types=1);

namespace NDCore\Events;

/**
 * Clase base de todos los eventos internos de ND Platform. Un evento es un
 * objeto de datos inmutable que describe algo que ya ocurrió (p. ej.
 * `ArticlePublished`), a diferencia de un hook de WordPress, que puede
 * modificar el flujo de ejecución.
 */
abstract class Event
{
    private bool $propagationStopped = false;

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
