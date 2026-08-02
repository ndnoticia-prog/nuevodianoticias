<?php

declare(strict_types=1);

namespace NDCore\Cache\Drivers;

use NDCore\Cache\Contracts\CacheDriver;
use Redis;
use RuntimeException;

/**
 * Driver de caché sobre Redis (extensión `phpredis`), recomendado en
 * producción para sitios de alto tráfico. Requiere la extensión `ext-redis`.
 */
final class RedisCacheDriver implements CacheDriver
{
    private ?Redis $connection = null;

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 6379,
        private readonly ?string $password = null,
        private readonly int $database = 0,
        private readonly string $prefix = 'nd_platform:',
        private readonly float $timeoutSeconds = 2.0,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $raw = $this->connection()->get($this->prefix . $key);

        if ($raw === false) {
            return $default;
        }

        return unserialize($raw, ['allowed_classes' => false]);
    }

    public function put(string $key, mixed $value, int $ttlSeconds): bool
    {
        $serialized = serialize($value);
        $fullKey = $this->prefix . $key;

        return $ttlSeconds > 0
            ? (bool) $this->connection()->setex($fullKey, $ttlSeconds, $serialized)
            : (bool) $this->connection()->set($fullKey, $serialized);
    }

    public function forget(string $key): bool
    {
        return $this->connection()->del($this->prefix . $key) > 0;
    }

    public function flush(): bool
    {
        $redis = $this->connection();
        $iterator = null;

        do {
            $keys = $redis->scan($iterator, $this->prefix . '*', 500);

            if (is_array($keys) && $keys !== []) {
                $redis->del($keys);
            }
        } while ($iterator !== null && $iterator !== 0);

        return true;
    }

    public function has(string $key): bool
    {
        return (bool) $this->connection()->exists($this->prefix . $key);
    }

    private function connection(): Redis
    {
        if ($this->connection instanceof Redis) {
            return $this->connection;
        }

        if (! class_exists(Redis::class)) {
            throw new RuntimeException(
                'NDCore\Cache\Drivers\RedisCacheDriver requiere la extensión PHP "redis" (phpredis), que no está instalada.'
            );
        }

        $redis = new Redis();

        if (! $redis->connect($this->host, $this->port, $this->timeoutSeconds)) {
            throw new RuntimeException(sprintf(
                'No se pudo conectar a Redis en %s:%d.',
                $this->host,
                $this->port
            ));
        }

        if ($this->password !== null && $this->password !== '') {
            $redis->auth($this->password);
        }

        if ($this->database !== 0) {
            $redis->select($this->database);
        }

        return $this->connection = $redis;
    }
}
