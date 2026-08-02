<?php

declare(strict_types=1);

namespace NDCore\Queue;

use NDCore\Database\DatabaseManager;
use NDCore\Queue\Contracts\ShouldQueue;
use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * Cola de trabajos respaldada por una tabla propia y procesada de forma
 * asíncrona por WP-Cron (ver {@see \NDCore\Scheduler\Scheduler}), para no
 * bloquear el hilo de una petición HTTP con trabajo pesado (llamadas a
 * proveedores de IA, procesamiento de medios, envíos de notificaciones).
 */
final class QueueManager
{
    private const TABLE = 'jobs';
    private const DEFAULT_MAX_ATTEMPTS = 3;

    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function push(ShouldQueue $job, int $delaySeconds = 0): int
    {
        $availableAt = gmdate('Y-m-d H:i:s', time() + max(0, $delaySeconds));

        return $this->db->insert(
            $this->db->table(self::TABLE),
            [
                'job_class' => $job::class,
                'payload' => (string) wp_json_encode($job->toPayload()),
                'attempts' => 0,
                'available_at' => $availableAt,
                'created_at' => current_time('mysql', true),
                'reserved_at' => null,
                'failed_at' => null,
                'error' => null,
            ],
            [
                'job_class' => '%s',
                'payload' => '%s',
                'attempts' => '%d',
                'available_at' => '%s',
                'created_at' => '%s',
            ]
        );
    }

    /**
     * Procesa hasta `$limit` trabajos pendientes que ya estén disponibles.
     * Devuelve el número de trabajos procesados (con éxito o con fallo).
     */
    public function processDueJobs(int $limit = 10): int
    {
        $table = $this->db->table(self::TABLE);
        $now = current_time('mysql', true);

        $rows = $this->db->select(
            "SELECT * FROM {$table} " .
            'WHERE reserved_at IS NULL AND failed_at IS NULL AND available_at <= %s ' .
            'ORDER BY id ASC LIMIT %d',
            [$now, $limit]
        );

        foreach ($rows as $row) {
            $this->reserve((int) $row['id']);
            $this->execute($row);
        }

        return count($rows);
    }

    public function retryFailed(int $jobId): bool
    {
        return (bool) $this->db->update(
            $this->db->table(self::TABLE),
            [
                'failed_at' => null,
                'reserved_at' => null,
                'attempts' => 0,
                'error' => null,
                'available_at' => current_time('mysql', true),
            ],
            ['id' => $jobId]
        );
    }

    public function countPending(): int
    {
        $table = $this->db->table(self::TABLE);
        $row = $this->db->selectOne("SELECT COUNT(*) AS total FROM {$table} WHERE failed_at IS NULL");

        return $row !== null ? (int) $row['total'] : 0;
    }

    public function countFailed(): int
    {
        $table = $this->db->table(self::TABLE);
        $row = $this->db->selectOne("SELECT COUNT(*) AS total FROM {$table} WHERE failed_at IS NOT NULL");

        return $row !== null ? (int) $row['total'] : 0;
    }

    private function reserve(int $id): void
    {
        $this->db->update(
            $this->db->table(self::TABLE),
            ['reserved_at' => current_time('mysql', true)],
            ['id' => $id]
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function execute(array $row): void
    {
        $table = $this->db->table(self::TABLE);
        $id = (int) $row['id'];
        $jobClass = (string) $row['job_class'];

        try {
            if (! is_a($jobClass, ShouldQueue::class, true)) {
                throw new RuntimeException(sprintf(
                    'La clase de trabajo "%s" no implementa %s.',
                    $jobClass,
                    ShouldQueue::class
                ));
            }

            $decodedPayload = json_decode((string) $row['payload'], true);
            $payload = is_array($decodedPayload) ? $decodedPayload : [];

            /** @var ShouldQueue $job */
            $job = $jobClass::fromPayload($payload);
            $job->handle();

            $this->db->delete($table, ['id' => $id]);
        } catch (Throwable $exception) {
            $attempts = (int) $row['attempts'] + 1;
            $maxAttempts = $this->maxAttemptsFor($jobClass);

            $this->db->update(
                $table,
                [
                    'attempts' => $attempts,
                    'reserved_at' => null,
                    'error' => $exception->getMessage(),
                    'failed_at' => $attempts >= $maxAttempts ? current_time('mysql', true) : null,
                ],
                ['id' => $id]
            );
        }
    }

    private function maxAttemptsFor(string $jobClass): int
    {
        if (! is_a($jobClass, Job::class, true)) {
            return self::DEFAULT_MAX_ATTEMPTS;
        }

        try {
            $default = (new ReflectionClass($jobClass))->getDefaultProperties()['maxAttempts'] ?? null;

            return is_int($default) ? $default : self::DEFAULT_MAX_ATTEMPTS;
        } catch (Throwable) {
            return self::DEFAULT_MAX_ATTEMPTS;
        }
    }
}
