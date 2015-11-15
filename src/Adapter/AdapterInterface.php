<?php

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;

interface AdapterInterface
{
    /**
     * @param array $config
     */
    public function __construct(array $config);

    /**
     * @param string $key
     *
     * @throws KeyNotFoundException
     *
     * @return mixed
     */
    public function get($key);

    /**
     * @param array $keys
     *
     * @return mixed[]
     */
    public function getMulti(array $keys);

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function set($key, $value);

    /**
     * @param array $keys
     *
     * @return bool
     */
    public function setMulti(array $data);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function contains($key);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function delete($key);

    /**
     * @param array $keys
     *
     * @return bool
     */
    public function deleteMulti(array $keys);

    /**
     * @return bool
     */
    public function flush();

    /**
     * @return string
     */
    public function getNamespace();

    /**
     * @param string $namespace
     */
    public function setNamespace($namespace);
}
