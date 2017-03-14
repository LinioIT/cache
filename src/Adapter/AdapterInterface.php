<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;

interface AdapterInterface
{
    public function __construct(array $config);

    /**
     * @throws KeyNotFoundException
     */
    public function get(string $key);

    public function getMulti(array $keys): array;

    public function set(string $key, $value): bool;

    public function setMulti(array $data): bool;

    public function contains(string $key): bool;

    public function delete(string $key): bool;

    public function deleteMulti(array $keys): bool;

    public function flush(): bool;

    public function getNamespace(): string;

    public function setNamespace(string $namespace);

    public function cacheNotFoundKeys(): bool;
}
