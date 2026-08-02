<?php

declare(strict_types=1);

namespace NDAi\Tasks;

use NDAi\AiManager;

/**
 * Capa de tareas editoriales sobre {@see AiManager}: construye el prompt
 * adecuado para cada necesidad concreta (titulares, SEO, redes sociales,
 * ...) en lugar de dejar que cada llamador escriba su propio prompt.
 */
final class ContentAssistant
{
    public function __construct(private readonly AiManager $ai)
    {
    }

    /**
     * @return list<string>
     */
    public function generateHeadlines(string $articleText, int $count = 5): array
    {
        $prompt = sprintf(
            "Eres editor de titulares para un medio de noticias en español. Genera %d titulares distintos, " .
            "concisos y atractivos (sin clickbait engañoso) para el siguiente artículo. Devuelve solo la " .
            "lista, un titular por línea, sin numeración ni comillas.\n\nArtículo:\n%s",
            $count,
            $articleText
        );

        return $this->splitLines($this->ai->complete($prompt));
    }

    public function generateSeoTitle(string $articleText): string
    {
        return $this->ai->complete(sprintf(
            'Genera un título SEO de máximo 60 caracteres para el siguiente artículo periodístico en ' .
            "español. Responde solo con el título, sin comillas ni explicaciones.\n\nArtículo:\n%s",
            $articleText
        ));
    }

    public function generateMetaDescription(string $articleText): string
    {
        return $this->ai->complete(sprintf(
            'Genera una meta description SEO de máximo 155 caracteres para el siguiente artículo ' .
            "periodístico en español. Responde solo con el texto, sin comillas.\n\nArtículo:\n%s",
            $articleText
        ));
    }

    /**
     * @return list<string>
     */
    public function generateTags(string $articleText, int $count = 8): array
    {
        $prompt = sprintf(
            "Sugiere %d etiquetas (tags) relevantes en español para el siguiente artículo periodístico. " .
            "Devuelve solo las etiquetas separadas por comas, en minúsculas, sin numeración.\n\nArtículo:\n%s",
            $count,
            $articleText
        );

        return $this->splitCommaList($this->ai->complete($prompt));
    }

    /**
     * @param list<string> $availableCategories
     *
     * @return list<string>
     */
    public function suggestCategories(string $articleText, array $availableCategories): array
    {
        if ($availableCategories === []) {
            return [];
        }

        $prompt = sprintf(
            "De esta lista de categorías disponibles: %s\n\nElige las que mejor correspondan al siguiente " .
            'artículo (máximo 3). Devuelve solo los nombres exactos de las categorías elegidas, separados ' .
            "por comas.\n\nArtículo:\n%s",
            implode(', ', $availableCategories),
            $articleText
        );

        $suggested = $this->splitCommaList($this->ai->complete($prompt));

        return array_values(array_intersect($availableCategories, $suggested));
    }

    public function generateSummary(string $articleText): string
    {
        return $this->ai->complete(sprintf(
            'Resume el siguiente artículo periodístico en español en un párrafo de 3-4 frases, de forma ' .
            "objetiva y neutral.\n\nArtículo:\n%s",
            $articleText
        ));
    }

    public function generateExcerpt(string $articleText, int $maxWords = 40): string
    {
        return $this->ai->complete(sprintf(
            'Genera un extracto (bajada) de máximo %d palabras para el siguiente artículo periodístico en ' .
            "español, que invite a seguir leyendo sin revelar el desenlace.\n\nArtículo:\n%s",
            $maxWords,
            $articleText
        ));
    }

    public function generateSocialPost(string $platform, string $articleText, string $url): string
    {
        $guidance = match ($platform) {
            'facebook' => 'Facebook: tono cercano, 1-2 frases, puede incluir una pregunta al final.',
            'instagram' => 'Instagram: tono cercano, incluye 3-5 hashtags relevantes en español al final.',
            'x' => 'X (Twitter): máximo 280 caracteres, directo y con gancho.',
            'linkedin' => 'LinkedIn: tono profesional, orientado al impacto o contexto de la noticia.',
            default => 'Red social genérica: tono neutral y directo.',
        };

        return $this->ai->complete(sprintf(
            'Escribe una publicación para %s promocionando el siguiente artículo periodístico en español. ' .
            "%s Incluye este enlace al final: %s\n\nArtículo:\n%s",
            $platform,
            $guidance,
            $url,
            $articleText
        ));
    }

    public function generateNewsletterBlurb(string $articleText, string $url): string
    {
        return $this->ai->complete(sprintf(
            'Escribe un bloque breve (2-3 frases) para un newsletter diario de noticias, presentando el ' .
            'siguiente artículo en español, con un tono informativo y directo. Incluye este enlace al ' .
            "final: %s\n\nArtículo:\n%s",
            $url,
            $articleText
        ));
    }

    public function generateVideoScript(string $articleText, int $durationSeconds = 60): string
    {
        return $this->ai->complete(sprintf(
            'Escribe un guion narrado en español para un video de noticias de aproximadamente %d segundos ' .
            '(unas %d palabras), basado en el siguiente artículo. Estructura: gancho inicial, desarrollo, ' .
            "cierre. Solo el texto narrado, sin acotaciones de cámara.\n\nArtículo:\n%s",
            $durationSeconds,
            (int) round($durationSeconds * 2.5),
            $articleText
        ));
    }

    /**
     * @return list<string>
     */
    private function splitLines(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $line): string => trim((string) preg_replace('/^[\-\*\d.\)]+\s*/', '', $line)),
            $lines
        )));
    }

    /**
     * @return list<string>
     */
    private function splitCommaList(string $text): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $text))));
    }
}
