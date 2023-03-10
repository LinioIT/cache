<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\InvalidConfigurationException;
use Linio\Component\Cache\Exception\KeyNotFoundException;
use Linio\Component\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;

class MysqlAdapterTest extends TestCase
{
    public const TABLE_NAME = 'key_value';
    public const TEST_NAMESPACE = 'mx';

    protected MysqlAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = $this->getMockBuilder(MysqlAdapter::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->adapter->setNamespace(static::TEST_NAMESPACE);
    }

    public function testIsValidatingConstructorParameterHost(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $adapter = new MysqlAdapter(
            [
                'host' => 'localhost',
                'dbname' => 'testdb',
                'username' => 'root',
                'password' => '',
                'table_name' => self::TABLE_NAME,
            ]
        );
    }

    public function testIsValidatingConstructorParameterPort(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $adapter = new MysqlAdapter(
            [
                'host' => 'localhost',
                'dbname' => 'testdb',
                'username' => 'root',
                'password' => '',
                'table_name' => self::TABLE_NAME,
            ]
        );
    }

    public function testIsValidatingConstructorParameterDbname(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $adapter = new MysqlAdapter(
            [
                'host' => 'localhost',
                'port' => 3306,
                'username' => 'root',
                'password' => '',
                'table_name' => self::TABLE_NAME,
            ]
        );
    }

    public function testIsValidatingConstructorParameterUsername(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $adapter = new MysqlAdapter(
            [
                'host' => 'localhost',
                'port' => 3306,
                'dbname' => 'testdb',
                'password' => '',
                'table_name' => self::TABLE_NAME,
            ]
        );
    }

    public function testIsValidatingConstructorParameterPassword(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $adapter = new MysqlAdapter(
            [
                'host' => 'localhost',
                'port' => 3306,
                'dbname' => 'testdb',
                'username' => 'root',
                'table_name' => self::TABLE_NAME,
            ]
        );
    }

    public function testIsValidatingConstructorParameterTableName(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $adapter = new MysqlAdapter(
            [
                'host' => 'localhost',
                'port' => 3306,
                'dbname' => 'testdb',
                'username' => 'root',
                'password' => '',
            ]
        );
    }

    public function testIsGetting(): void
    {
        $expectedQuery = sprintf('SELECT `value` FROM `%s` WHERE `key` = :key LIMIT 1', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('fetchColumn')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':foo']), 0)
            ->will($this->returnValue(['bar']));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->get('foo');

        $this->assertEquals('bar', $actual);
    }

    public function testIsGettingNonexistentKey(): void
    {
        $this->expectException(KeyNotFoundException::class);

        $expectedQuery = sprintf('SELECT `value` FROM `%s` WHERE `key` = :key LIMIT 1', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('fetchColumn')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':foo']), 0)
            ->will($this->returnValue([]));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $this->adapter->get('foo');
    }

    public function testIsFindingKey(): void
    {
        $expectedQuery = sprintf('SELECT `value` FROM `%s` WHERE `key` = :key LIMIT 1', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('fetchColumn')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':foo']), 0)
            ->will($this->returnValue(['bar']));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->contains('foo');

        $this->assertTrue($actual);
    }

    public function testIsNotFindingKey(): void
    {
        $expectedQuery = sprintf('SELECT `value` FROM `%s` WHERE `key` = :key LIMIT 1', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('fetchColumn')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':baz']), 0)
            ->will($this->returnValue([]));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->contains('baz');

        $this->assertFalse($actual);
    }

    public function testIsGettingMultipleKeys(): void
    {
        $expectedQuery = sprintf('SELECT `key`, `value` FROM `%s` WHERE `key` IN(?,?)', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('fetchKeyPairs')
            ->with($this->equalTo($expectedQuery), $this->equalTo([static::TEST_NAMESPACE . ':foo', static::TEST_NAMESPACE . ':fooz']))
            ->will($this->returnValue([static::TEST_NAMESPACE . ':foo' => 'bar', static::TEST_NAMESPACE . ':fooz' => 'baz']));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => 'bar', 'fooz' => 'baz'], $actual);
    }

    public function testIsGettingMultipleKeysWithInvalidKeys(): void
    {
        $expectedQuery = sprintf('SELECT `key`, `value` FROM `%s` WHERE `key` IN(?,?)', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('fetchKeyPairs')
            ->with($this->equalTo($expectedQuery), $this->equalTo([static::TEST_NAMESPACE . ':foo', static::TEST_NAMESPACE . ':nop']))
            ->will($this->returnValue([static::TEST_NAMESPACE . ':foo' => 'bar']));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->getMulti(['foo', 'nop']);

        $this->assertEquals(['foo' => 'bar'], $actual);
    }

    public function testIsSettingKey(): void
    {
        $expectedQuery = sprintf('INSERT INTO `%s` (`key`, `value`) VALUES(:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':foo', 'value' => 'bar']))
            ->will($this->returnValue(1));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->set('foo', 'bar');

        $this->assertTrue($actual);
    }

    public function testIsSettingMultipleKeys(): void
    {
        $expectedQuery = sprintf('INSERT INTO `%s` (`key`, `value`) VALUES (?, ?),(?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo([static::TEST_NAMESPACE . ':foo', 'bar', static::TEST_NAMESPACE . ':fooz', 'baz']))
            ->will($this->returnValue(2));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->setMulti(['foo' => 'bar', 'fooz' => 'baz']);

        $this->assertTrue($actual);
    }

    public function testIsDeletingKey(): void
    {
        $expectedQuery = sprintf('DELETE FROM `%s` WHERE `key` = :key', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':foo']))
            ->will($this->returnValue(1));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->delete('foo');

        $this->assertTrue($actual);
    }

    public function testIsDeletingMultipleKeys(): void
    {
        $expectedQuery = sprintf('DELETE FROM `%s` WHERE `key` IN (?,?)', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo([static::TEST_NAMESPACE . ':foo', static::TEST_NAMESPACE . ':fooz']))
            ->will($this->returnValue(1));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->deleteMulti(['foo', 'fooz']);

        $this->assertTrue($actual);
    }

    public function testIsDeletingNonexistentKey(): void
    {
        $expectedQuery = sprintf('DELETE FROM `%s` WHERE `key` = :key', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':nop']))
            ->will($this->returnValue(0));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->delete('nop');

        $this->assertTrue($actual);
    }

    public function testIsDeletingNonexistentMultipleKeys(): void
    {
        $expectedQuery = sprintf('DELETE FROM `%s` WHERE `key` IN (?,?)', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo([static::TEST_NAMESPACE . ':foo', static::TEST_NAMESPACE . ':nop']))
            ->will($this->returnValue(1));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->deleteMulti(['foo', 'nop']);

        $this->assertTrue($actual);
    }

    public function testIsFlushingData(): void
    {
        $expectedQuery = sprintf('DELETE FROM `%s`', self::TABLE_NAME);

        $mockDb = $this->createMock(DatabaseManager::class);
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery))
            ->will($this->returnValue(1));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->flush();

        $this->assertTrue($actual);
    }
}
