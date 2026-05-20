<?php

namespace tests\oihana\mysql;

use oihana\mysql\MysqlDSN;
use PHPUnit\Framework\TestCase;

final class MysqlDSNTest extends TestCase
{
    public function testDefaults(): void
    {
        $dsn = new MysqlDSN();

        $this->assertSame('utf8mb4', $dsn->charset);
        $this->assertSame('localhost', $dsn->host);
        $this->assertSame(3306, $dsn->port);
        $this->assertNull($dsn->dbname);
        $this->assertNull($dsn->unixSocket);
    }

    public function testToArrayReturnsExpectedStructure(): void
    {
        $dsn = new MysqlDSN([
            MysqlDSN::HOST    => '127.0.0.1',
            MysqlDSN::PORT    => 3307,
            MysqlDSN::DBNAME  => 'example_db',
            MysqlDSN::CHARSET => 'latin1',
            MysqlDSN::UNIX_SOCKET => '/tmp/mysql.sock',
        ]);

        $expected = [
            MysqlDSN::HOST        => '127.0.0.1',
            MysqlDSN::PORT        => 3307,
            MysqlDSN::DBNAME      => 'example_db',
            MysqlDSN::CHARSET     => 'latin1',
            MysqlDSN::UNIX_SOCKET => '/tmp/mysql.sock',
        ];

        $this->assertSame($expected, $dsn->toArray());
    }

    public function testToStringGeneratesCorrectDSN(): void
    {
        $dsn = new MysqlDSN([
            MysqlDSN::HOST    => '127.0.0.1',
            MysqlDSN::PORT    => 3306,
            MysqlDSN::DBNAME  => 'test_db',
            MysqlDSN::CHARSET => 'utf8',
        ]);

        $expected = 'mysql:host=127.0.0.1;port=3306;dbname=test_db;charset=utf8';

        $this->assertSame($expected, (string) $dsn);
    }

    public function testToStringIncludesUnixSocket(): void
    {
        $dsn = new MysqlDSN([
            MysqlDSN::HOST         => 'localhost',
            MysqlDSN::PORT         => 3306,
            MysqlDSN::DBNAME       => 'mydb',
            MysqlDSN::UNIX_SOCKET  => '/var/run/mysqld/mysqld.sock',
        ]);

        $expected = 'mysql:host=localhost;port=3306;dbname=mydb;charset=utf8mb4;unix_socket=/var/run/mysqld/mysqld.sock';

        $this->assertSame($expected, (string) $dsn);
    }

    public function testToStringOmitsDbnameIfNull(): void
    {
        $dsn = new MysqlDSN([
            MysqlDSN::HOST    => 'localhost',
            MysqlDSN::PORT    => 3306,
            MysqlDSN::CHARSET => 'utf8mb4',
        ]);

        $expected = 'mysql:host=localhost;port=3306;charset=utf8mb4';

        $this->assertSame($expected, (string) $dsn);
    }
}