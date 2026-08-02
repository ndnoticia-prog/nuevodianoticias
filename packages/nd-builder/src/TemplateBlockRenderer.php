<?php

declare(strict_types=1);

namespace NDBuilder;

use NDBuilder\Contracts\BlockRenderer;
use RuntimeException;

/**
 * Puente entre el motor de renderizado (lógica, en nd-builder) y la
 * presentación (markup, en el tema activo): busca la primera plantilla
 * existente entre `$candidateTemplates` con `locate_template()` —igual que
 * cualquier `template-part` de WordPress— y la incluye con `$block`
 * disponible en su scope. Ningún HTML vive en este paquete.
 */
final class TemplateBlockRenderer implements BlockRenderer
{
    /**
     * @param list<string> $candidateTemplates Rutas relativas sin extensión (p. ej. "template-parts/blocks/hero"), probadas en orden.
     */
    public function __construct(private readonly array $candidateTemplates)
    {
    }

    public function render(Block $block): string
    {
        $templates = array_map(
            static fn (string $template): string => $template . '.php',
            $this->candidateTemplates
        );

        $located = locate_template($templates, false);

        if ($located === '') {
            throw new RuntimeException(sprintf(
                'No se encontró ninguna plantilla para el bloque "%s". Rutas probadas: %s.',
                $block->type,
                implode(', ', $templates)
            ));
        }

        return $this->renderTemplate($located, $block);
    }

    private function renderTemplate(string $templatePath, Block $block): string
    {
        $renderIsolated = static function (string $templatePath, Block $block): string {
            ob_start();
            include $templatePath;

            return (string) ob_get_clean();
        };

        return $renderIsolated($templatePath, $block);
    }
}
