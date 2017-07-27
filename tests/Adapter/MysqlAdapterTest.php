<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

class MysqlAdapterTest extends \PHPUnit_Framework_TestCase
{
    const TABLE_NAME = 'key_value';
    const TEST_NAMESPACE = 'mx';

    /**
     * @var MysqlAdapter
     */
    protected $adapter;

    protected function setUp()
    {
        $this->adapter = $this->getMockBuilder('Linio\Component\Cache\Adapter\MysqlAdapter')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->adapter->setNamespace(static::TEST_NAMESPACE);
    }

    /**
     * @expectedException \Linio\Component\Cache\Exception\InvalidConfigurationException
     */
    public function testIsValidatingConstructorParameterHost()
    {
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

    /**
     * @expectedException \Linio\Component\Cache\Exception\InvalidConfigurationException
     */
    public function testIsValidatingConstructorParameterPort()
    {
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

    /**
     * @expectedException \Linio\Component\Cache\Exception\InvalidConfigurationException
     */
    public function testIsValidatingConstructorParameterDbname()
    {
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

    /**
     * @expectedException \Linio\Component\Cache\Exception\InvalidConfigurationException
     */
    public function testIsValidatingConstructorParameterUsername()
    {
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

    /**
     * @expectedException \Linio\Component\Cache\Exception\InvalidConfigurationException
     */
    public function testIsValidatingConstructorParameterPassword()
    {
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

    /**
     * @expectedException \Linio\Component\Cache\Exception\InvalidConfigurationException
     */
    public function testIsValidatingConstructorParameterTable_name()
    {
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

    public function testIsGetting()
    {
        $expectedQuery = sprintf('SELECT `value` FROM `%s` WHERE `key` = :key LIMIT 1', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('fetchColumn')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':foo']), 0)
            ->will($this->returnValue(['bar']));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->get('foo');

        $this->assertEquals('bar', $actual);
    }

    /**
     * @expectedException \Linio\Component\Cache\Exception\KeyNotFoundException
     */
    public function testIsGettingInexistentKey()
    {
        $expectedQuery = sprintf('SELECT `value` FROM `%s` WHERE `key` = :key LIMIT 1', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('fetchColumn')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':foo']), 0)
            ->will($this->returnValue([]));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->get('foo');
    }

    public function testIsFindingKey()
    {
        $expectedQuery = sprintf('SELECT `value` FROM `%s` WHERE `key` = :key LIMIT 1', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('fetchColumn')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':foo']), 0)
            ->will($this->returnValue(['bar']));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->contains('foo');

        $this->assertTrue($actual);
    }

    public function testIsNotFindingKey()
    {
        $expectedQuery = sprintf('SELECT `value` FROM `%s` WHERE `key` = :key LIMIT 1', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('fetchColumn')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':baz']), 0)
            ->will($this->returnValue([]));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->contains('baz');

        $this->assertFalse($actual);
    }

    public function testIsGettingMultipleKeys()
    {
        $expectedQuery = sprintf('SELECT `key`, `value` FROM `%s` WHERE `key` IN(?,?)', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('fetchKeyPairs')
            ->with($this->equalTo($expectedQuery), $this->equalTo([static::TEST_NAMESPACE . ':foo', static::TEST_NAMESPACE . ':fooz']))
            ->will($this->returnValue([static::TEST_NAMESPACE . ':foo' => 'bar', static::TEST_NAMESPACE . ':fooz' => 'baz']));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => 'bar', 'fooz' => 'baz'], $actual);
    }

    public function testIsGettingMultipleKeysWithInvalidKeys()
    {
        $expectedQuery = sprintf('SELECT `key`, `value` FROM `%s` WHERE `key` IN(?,?)', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('fetchKeyPairs')
            ->with($this->equalTo($expectedQuery), $this->equalTo([static::TEST_NAMESPACE . ':foo', static::TEST_NAMESPACE . ':nop']))
            ->will($this->returnValue([static::TEST_NAMESPACE . ':foo' => 'bar']));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->getMulti(['foo', 'nop']);

        $this->assertEquals(['foo' => 'bar'], $actual);
    }

    public function testIsSettingKey()
    {
        $expectedQuery = sprintf('INSERT INTO `%s` (`key`, `value`) VALUES(:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':foo', 'value' => 'bar']))
            ->will($this->returnValue(1));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->set('foo', 'bar');

        $this->assertTrue($actual);
    }

    public function testIsSettingMultipleKeys()
    {
        $expectedQuery = sprintf('INSERT INTO `%s` (`key`, `value`) VALUES (?, ?),(?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo([static::TEST_NAMESPACE . ':foo', 'bar', static::TEST_NAMESPACE . ':fooz', 'baz']))
            ->will($this->returnValue(2));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->setMulti(['foo' => 'bar', 'fooz' => 'baz']);

        $this->assertTrue($actual);
    }

    public function testIsDeletingKey()
    {
        $expectedQuery = sprintf('DELETE FROM `%s` WHERE `key` = :key', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':foo']))
            ->will($this->returnValue(1));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->delete('foo');

        $this->assertTrue($actual);
    }

    public function testIsDeletingMultipleKeys()
    {
        $expectedQuery = sprintf('DELETE FROM `%s` WHERE `key` IN (?,?)', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo([static::TEST_NAMESPACE . ':foo', static::TEST_NAMESPACE . ':fooz']))
            ->will($this->returnValue(1));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->deleteMulti(['foo', 'fooz']);

        $this->assertTrue($actual);
    }

    public function testIsDeletingInexistentKey()
    {
        $expectedQuery = sprintf('DELETE FROM `%s` WHERE `key` = :key', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo(['key' => static::TEST_NAMESPACE . ':nop']))
            ->will($this->returnValue(0));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->delete('nop');

        $this->assertTrue($actual);
    }

    public function testIsDeletingInexistentMultipleKeys()
    {
        $expectedQuery = sprintf('DELETE FROM `%s` WHERE `key` IN (?,?)', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery), $this->equalTo([static::TEST_NAMESPACE . ':foo', static::TEST_NAMESPACE . ':nop']))
            ->will($this->returnValue(1));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->deleteMulti(['foo', 'nop']);

        $this->assertTrue($actual);
    }

    public function testIsFlushingData()
    {
        $expectedQuery = sprintf('DELETE FROM `%s`', self::TABLE_NAME);

        $mockDb = $this->createMock('Linio\Component\Database\DatabaseManager');
        $mockDb->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($expectedQuery))
            ->will($this->returnValue(true));
        $this->adapter->setDbManager($mockDb);
        $this->adapter->setTableName(self::TABLE_NAME);

        $actual = $this->adapter->flush();

        $this->assertTrue($actual);
    }
}
