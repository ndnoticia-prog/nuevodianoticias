<?php

declare(strict_types=1);

namespace NDMedia\Optimization;

/**
 * Sustituye el atributo `sizes` que WordPress calcula por defecto (basado
 * únicamente en el ancho intrínseco de la imagen, casi siempre incorrecto
 * para una maquetación en cuadrícula) por uno alineado a los breakpoints
 * reales de nd-theme (ver resources/scss/_variables.scss).
 */
final class ResponsiveImageSizer
{
    private const SIZES = '(max-width: 480px) 100vw, (max-width: 768px) 50vw, (max-width: 1024px) 33vw, 25vw';

    public function filterSizes(string $sizes): string
    {
        return self::SIZES;
    }
}
