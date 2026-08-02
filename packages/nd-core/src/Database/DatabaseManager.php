<?php

declare(strict_types=1);

namespace NDCore\Database;

use RuntimeException;
use wpdb;

/**
 * Única puerta de acceso de ND Platform a la base de datos. Envuelve `$wpdb`
 * y obliga a usar sentencias preparadas en toda la plataforma.
 */
final class DatabaseManager
{
    private readonly wpdb $wpdb;

    public function __construct(?wpdb $wpdb = null)
    {
        if ($wpdb === null) {
            global $wpdb;
        }

        $this->wpdb = $wpdb;
    }

    public function prefix(): string
    {
        return $this->wpdb->prefix . 'nd_';
    }

    public function table(string $name): string
    {
        return $this->prefix() . ltrim($name, '_');
    }

    /**
     * @param list<mixed> $bindings
     *
     * @return list<array<string, mixed>>
     */
    public function select(string $query, array $bindings = []): array
    {
        $prepared = $this->prepare($query, $bindings);
        $results = $this->wpdb->get_results($prepared, ARRAY_A);

        return is_array($results) ? $results : [];
    }

    /**
     * @param list<mixed> $bindings
     *
     * @return array<string, mixed>|null
     */
    public function selectOne(string $query, array $bindings = []): ?array
    {
        $prepared = $this->prepare($query, $bindings);
        $result = $this->wpdb->get_row($prepared, ARRAY_A);

        return is_array($result) ? $result : null;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string>|null $formats Formatos `%s`/`%d`/`%f` por columna.
     */
    public function insert(string $table, array $data, ?array $formats = null): int
    {
        $result = $this->wpdb->insert($table, $data, $formats !== null ? array_values($formats) : null);

        if ($result === false) {
            throw new RuntimeException(sprintf('No se pudo insertar en "%s": %s', $table, $this->wpdb->last_error));
        }

        return (int) $this->wpdb->insert_id;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where
     */
    public function update(string $table, array $data, array $where): int
    {
        $result = $this->wpdb->update($table, $data, $where);

        if ($result === false) {
            throw new RuntimeException(sprintf('No se pudo actualizar "%s": %s', $table, $this->wpdb->last_error));
        }

        return (int) $result;
    }

    /**
     * @param array<string, mixed> $where
     */
    public function delete(string $table, array $where): int
    {
        $result = $this->wpdb->delete($table, $where);

        if ($result === false) {
            throw new RuntimeException(sprintf('No se pudo eliminar de "%s": %s', $table, $this->wpdb->last_error));
        }

        return (int) $result;
    }

    /**
     * @param list<mixed> $bindings
     */
    public function statement(string $query, array $bindings = []): bool
    {
        $prepared = $this->prepare($query, $bindings);

        return $this->wpdb->query($prepared) !== false;
    }

    public function lastError(): string
    {
        return $this->wpdb->last_error;
    }

    public function charsetCollate(): string
    {
        return $this->wpdb->get_charset_collate();
    }

    /**
     * @param list<mixed> $bindings
     */
    private function prepare(string $query, array $bindings): string
    {
        return $bindings === [] ? $query : $this->wpdb->prepare($query, $bindings);
    }
}
