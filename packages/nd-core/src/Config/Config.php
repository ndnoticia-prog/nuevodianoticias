<?php

declare(strict_types=1);

namespace NDCore\Config;

/**
 * Repositorio de configuración en memoria con acceso por notación de puntos.
 *
 * Cada archivo `config/{nombre}.php` que devuelve un array PHP se carga bajo
 * la clave de nivel superior `{nombre}` (p. ej. `config/cache.php` → `cache.*`).
 */
final class Config
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(private array $items = [])
    {
    }

    /**
     * Carga todos los archivos `*.php` de un directorio como namespaces de configuración.
     */
    public function loadDirectory(string $directory): void
    {
        $directory = rtrim($directory, '/');

        if (! is_dir($directory)) {
            return;
        }

        foreach (glob($directory . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            $value = require $file;

            if (! is_array($value)) {
                continue;
            }

            $this->items[$key] = isset($this->items[$key]) && is_array($this->items[$key])
                ? array_replace_recursive($this->items[$key], $value)
                : $value;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        $target = &$this->items;

        foreach ($segments as $segment) {
            if (! isset($target[$segment]) || ! is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }

        $target[$lastSegment] = $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }
}
