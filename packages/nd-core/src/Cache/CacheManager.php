<?php

declare(strict_types=1);

namespace NDCore\Cache;

use Closure;
use InvalidArgumentException;
use NDCore\Cache\Contracts\CacheDriver;
use NDCore\Cache\Drivers\ObjectCacheDriver;
use NDCore\Cache\Drivers\RedisCacheDriver;
use NDCore\Cache\Drivers\TransientCacheDriver;
use NDCore\Config\Config;

/**
 * Punto único de acceso a caché para toda la plataforma. Resuelve el driver
 * activo a partir de `config('cache.driver')` de forma perezosa.
 */
final class CacheManager
{
    private const DEFAULT_TTL = 3600;

    /**
     * @var array<string, CacheDriver>
     */
    private array $resolvedDrivers = [];

    public function __construct(private readonly Config $config)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->driver()->get($key, $default);
    }

    public function put(string $key, mixed $value, ?int $ttlSeconds = null): bool
    {
        return $this->driver()->put($key, $value, $ttlSeconds ?? (int) $this->config->get('cache.ttl', self::DEFAULT_TTL));
    }

    public function forget(string $key): bool
    {
        return $this->driver()->forget($key);
    }

    public function flush(): bool
    {
        return $this->driver()->flush();
    }

    public function has(string $key): bool
    {
        return $this->driver()->has($key);
    }

    /**
     * Devuelve el valor cacheado si existe; si no, ejecuta `$callback`, lo
     * almacena y lo devuelve.
     */
    public function remember(string $key, Closure $callback, ?int $ttlSeconds = null): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();

        $this->put($key, $value, $ttlSeconds);

        return $value;
    }

    public function driver(?string $name = null): CacheDriver
    {
        $name ??= (string) $this->config->get('cache.driver', 'transient');

        return $this->resolvedDrivers[$name] ??= $this->createDriver($name);
    }

    private function createDriver(string $name): CacheDriver
    {
        if ($name === 'transient') {
            return new TransientCacheDriver();
        }

        if ($name === 'object-cache') {
            return new ObjectCacheDriver();
        }

        if ($name === 'redis') {
            $password = $this->config->get('cache.redis.password');

            return new RedisCacheDriver(
                host: (string) $this->config->get('cache.redis.host', '127.0.0.1'),
                port: (int) $this->config->get('cache.redis.port', 6379),
                password: $password === null ? null : (string) $password,
                database: (int) $this->config->get('cache.redis.database', 0),
                prefix: (string) $this->config->get('cache.redis.prefix', 'nd_platform:'),
            );
        }

        throw new InvalidArgumentException(sprintf('Driver de caché desconocido: "%s".', $name));
    }
}
