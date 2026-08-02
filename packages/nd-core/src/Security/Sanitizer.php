<?php

declare(strict_types=1);

namespace NDCore\Security;

/**
 * Centraliza la sanitización de entrada de usuario. Ningún paquete debe
 * confiar en `$_POST`/`$_GET` sin pasar antes por aquí.
 */
final class Sanitizer
{
    public function text(string $value): string
    {
        return sanitize_text_field($value);
    }

    public function textarea(string $value): string
    {
        return sanitize_textarea_field($value);
    }

    public function email(string $value): string
    {
        return sanitize_email($value);
    }

    public function url(string $value): string
    {
        return esc_url_raw($value);
    }

    public function slug(string $value): string
    {
        return sanitize_title($value);
    }

    public function key(string $value): string
    {
        return sanitize_key($value);
    }

    public function int(mixed $value): int
    {
        return (int) $value;
    }

    public function float(mixed $value): float
    {
        return (float) $value;
    }

    public function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param array<string, string>|null $allowedHtml Formato de {@see wp_kses()}; por defecto usa el conjunto permitido para `post`.
     */
    public function html(string $value, ?array $allowedHtml = null): string
    {
        return wp_kses($value, $allowedHtml ?? wp_kses_allowed_html('post'));
    }

    /**
     * Sanitiza un array asociativo según un mapa `campo => tipo`.
     * Tipos soportados: text, textarea, email, url, slug, key, int, float, bool.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     *
     * @return array<string, mixed>
     */
    public function array(array $data, array $rules): array
    {
        $sanitized = [];

        foreach ($rules as $field => $type) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            $sanitized[$field] = match ($type) {
                'text' => $this->text((string) $value),
                'textarea' => $this->textarea((string) $value),
                'email' => $this->email((string) $value),
                'url' => $this->url((string) $value),
                'slug' => $this->slug((string) $value),
                'key' => $this->key((string) $value),
                'int' => $this->int($value),
                'float' => $this->float($value),
                'bool' => $this->bool($value),
                default => $value,
            };
        }

        return $sanitized;
    }
}
