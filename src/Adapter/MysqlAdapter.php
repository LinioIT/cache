<?php

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\InvalidConfigurationException;
use Linio\Component\Cache\Exception\KeyNotFoundException;
use Linio\Component\Database\DatabaseManager;

class MysqlAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * @var DatabaseManager
     */
    protected $dbManager;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config = [])
    {
        $config = $this->validateConnectionOptions($config);

        $connectionOptions = $this->getConnectionOptions($config);

        $dbManager = new DatabaseManager();
        $dbManager->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions);

        $this->setDbManager($dbManager);
        $this->setTableName($config['table_name']);

        $this->checkTableCreated($config);
    }

    /**
     * @param \PDO $dbManager
     */
    public function setDbManager($dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $sql = sprintf('SELECT `value` FROM `%s` WHERE `key` = :key LIMIT 1', $this->tableName);

        $results = $this->dbManager->fetchColumn($sql, ['key' => $this->addNamespaceToKey($key)], 0);

        if (!$results) {
            throw new KeyNotFoundException();
        }

        return $results[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getMulti(array $keys)
    {
        $placeholders = array_fill(1, count($keys), '?');
        $sql = sprintf('SELECT `key`, `value` FROM `%s` WHERE `key` IN(%s)', $this->tableName, implode(',', $placeholders));

        $namespacedKeys = $this->addNamespaceToKeys($keys);
        $namespacedData = $this->dbManager->fetchKeyPairs($sql, $namespacedKeys);
        $data = $this->removeNamespaceFromKeys($namespacedData);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $sql = sprintf('INSERT INTO `%s` (`key`, `value`) VALUES(:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', $this->tableName);

        $this->dbManager->execute($sql, ['key' => $this->addNamespaceToKey($key), 'value' => $value]);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function setMulti(array $data)
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

    /**
     * {@inheritdoc}
     */
    public function contains($key)
    {
        try {
            $this->get($key);
        } catch (KeyNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $sql = sprintf('DELETE FROM `%s` WHERE `key` = :key', $this->tableName);

        $this->dbManager->execute($sql, ['key' => $this->addNamespaceToKey($key)]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMulti(array $keys)
    {
        $placeholders = array_fill(1, count($keys), '?');
        $sql = sprintf('DELETE FROM `%s` WHERE `key` IN (%s)', $this->tableName, implode(',', $placeholders));

        $namespacedKeys = $this->addNamespaceToKeys($keys);
        $this->dbManager->execute($sql, $namespacedKeys);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $sql = sprintf('DELETE FROM `%s`', $this->tableName);

        $this->dbManager->execute($sql);

        return true;
    }

    /**
     * @param array $config
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @throws InvalidConfigurationException
     * @return array
     *
     */
    protected function validateConnectionOptions(array $config)
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

    /**
     * @param array $config
     */
    protected function checkTableCreated(array $config)
    {
        if (!isset($config['ensure_table_created'])) {
            $this->dbManager->execute(
                'CREATE TABLE IF NOT EXISTS `key_value` (`key` varchar(255) NOT NULL, `value` varchar(10000) DEFAULT NULL, PRIMARY KEY (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
            );
        }
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function getConnectionOptions(array $config)
    {
        $connectionOptions = [
            'host' => $config['host'],
            'port' => $config['port'],
            'dbname' => $config['dbname'],
            'username' => $config['username'],
            'password' => $config['password'],
        ];

        return $connectionOptions;
    }
}
