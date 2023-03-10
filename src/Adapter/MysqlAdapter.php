<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\InvalidConfigurationException;
use Linio\Component\Cache\Exception\KeyNotFoundException;
use Linio\Component\Database\DatabaseManager;

class MysqlAdapter extends AbstractAdapter implements AdapterInterface
{
    protected DatabaseManager $dbManager;
    protected string $tableName;

    public function __construct(array $config = [])
    {
        $this->validateConnectionOptions($config);
        $connectionOptions = $this->getConnectionOptions($config);

        if (isset($config['cache_not_found_keys'])) {
            $this->cacheNotFoundKeys = (bool) $config['cache_not_found_keys'];
        }

        $dbManager = new DatabaseManager();
        $dbManager->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions);

        $this->setDbManager($dbManager);
        $this->setTableName($config['table_name']);

        $this->checkTableCreated($config);
    }

    public function setDbManager(DatabaseManager $dbManager): void
    {
        $this->dbManager = $dbManager;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * @return mixed
     * @throws KeyNotFoundException when the key does not exist
     */
    public function get(string $key)
    {
        $sql = sprintf('SELECT `value` FROM `%s` WHERE `key` = :key LIMIT 1', $this->tableName);

        $results = $this->dbManager->fetchColumn($sql, ['key' => $this->addNamespaceToKey($key)], 0);

        if (!$results) {
            throw new KeyNotFoundException();
        }

        return $results[0];
    }

    public function getMulti(array $keys): array
    {
        $placeholders = array_fill(1, count($keys), '?');
        $sql = sprintf('SELECT `key`, `value` FROM `%s` WHERE `key` IN(%s)', $this->tableName, implode(',', $placeholders));

        $namespacedKeys = $this->addNamespaceToKeys($keys);
        $namespacedData = $this->dbManager->fetchKeyPairs($sql, $namespacedKeys);

        return $this->removeNamespaceFromKeys($namespacedData);
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): bool
    {
        $sql = sprintf('INSERT INTO `%s` (`key`, `value`) VALUES(:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', $this->tableName);

        $this->dbManager->execute($sql, ['key' => $this->addNamespaceToKey($key), 'value' => $value]);

        return true;
    }

    public function setMulti(array $data): bool
    {
        $placeholders = [];
        $keyValues = [];
        foreach ($data as $key => $value) {
            $placeholders[] = '(?, ?)';
            $keyValues[] = $this->addNamespaceToKey($key);
            $keyValues[] = $value;
        }

        $sql = sprintf(
            'INSERT INTO `%s` (`key`, `value`) VALUES %s ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            $this->tableName,
            implode(',', $placeholders)
        );

        $this->dbManager->execute($sql, $keyValues);

        return true;
    }

    public function contains(string $key): bool
    {
        try {
            $this->get($key);
        } catch (KeyNotFoundException $e) {
            return false;
        }

        return true;
    }

    public function delete(string $key): bool
    {
        $sql = sprintf('DELETE FROM `%s` WHERE `key` = :key', $this->tableName);

        $this->dbManager->execute($sql, ['key' => $this->addNamespaceToKey($key)]);

        return true;
    }

    public function deleteMulti(array $keys): bool
    {
        $placeholders = array_fill(1, count($keys), '?');
        $sql = sprintf('DELETE FROM `%s` WHERE `key` IN (%s)', $this->tableName, implode(',', $placeholders));

        $namespacedKeys = $this->addNamespaceToKeys($keys);
        $this->dbManager->execute($sql, $namespacedKeys);

        return true;
    }

    public function flush(): bool
    {
        $sql = sprintf('DELETE FROM `%s`', $this->tableName);

        $this->dbManager->execute($sql);

        return true;
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateConnectionOptions(array $config): void
    {
        if (!isset($config['host'])) {
            throw new InvalidConfigurationException('Missing configuration parameter: host');
        }

        if (!isset($config['port'])) {
            throw new InvalidConfigurationException('Missing configuration parameter: port');
        }

        if (!isset($config['dbname'])) {
            throw new InvalidConfigurationException('Missing configuration parameter: dbname');
        }

        if (!isset($config['username'])) {
            throw new InvalidConfigurationException('Missing configuration parameter: username');
        }

        if (!isset($config['password'])) {
            throw new InvalidConfigurationException('Missing configuration parameter: password');
        }

        if (!isset($config['table_name'])) {
            throw new InvalidConfigurationException('Missing configuration parameter: table_name');
        }
    }

    protected function checkTableCreated(array $config): void
    {
        if (!isset($config['ensure_table_created'])) {
            $this->dbManager->execute(
                'CREATE TABLE IF NOT EXISTS `key_value` (`key` varchar(255) NOT NULL, `value` varchar(10000) DEFAULT NULL, PRIMARY KEY (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
            );
        }
    }

    protected function getConnectionOptions(array $config): array
    {
        return [
            'host' => $config['host'],
            'port' => $config['port'],
            'dbname' => $config['dbname'],
            'username' => $config['username'],
            'password' => $config['password'],
        ];
    }
}
