<?php

declare(strict_types=1);

namespace NDCore\Cache\Contracts;

interface CacheDriver
{
    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value, int $ttlSeconds): bool;

    public function forget(string $key): bool;

    public function flush(): bool;

    public function has(string $key): bool;
}
