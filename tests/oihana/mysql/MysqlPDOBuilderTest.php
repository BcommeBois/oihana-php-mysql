<?php

namespace tests\oihana\mysql;

use PDO;
use InvalidArgumentException;
use oihana\mysql\MysqlPDOBuilder;
use PHPUnit\Framework\TestCase;

class DummyPDOBuilder extends MysqlPDOBuilder
{
    public PDO $mock;

    protected function createPDO(string $dsn, ?string $user, ?string $pass, array $options): PDO
    {
        return $this->mock ?? new PDO('sqlite::memory:') ; // fallback test
    }
}

class MysqlPDOBuilderTest extends TestCase
{
    public function testBuilderWithValidConfiguration(): void
    {
        $builder = new MysqlPDOBuilder
        ([
            'host'     => 'localhost',
            'dbname'   => 'test',
            'username' => 'root',
            'password' => 'root',
            'validate' => false, // skip live connection
        ]);

        $this->assertSame('root', $builder->username);
        $this->assertSame('root', $builder->password);
        $this->assertFalse($builder->skipDbName);
        $this->assertFalse($builder->validate);
    }

    public function testToArrayMasksPassword(): void
    {
        $builder = new MysqlPDOBuilder
        ([
            'host'     => 'localhost',
            'dbname'   => 'test',
            'username' => 'user',
            'password' => 'secret123',
            'validate' => false,
        ]);

        $array = $builder->toArray();
        $this->assertSame('*********', $array['password']);
        $this->assertSame('user', $array['username']);
        $this->assertArrayHasKey('dsn', $array);
        $this->assertIsArray($array['options']);
    }

    public function testThrowsIfMissingHost(): void
    {
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage('MySQL DSN is missing the host.');

        $builder = new MysqlPDOBuilder
        ([
            'host'     => '', // Important : clean explicitly
            'dbname'   => 'test',
            'username' => 'user',
            'validate' => true,
        ]);

        $builder->validate();
    }

    public function testThrowsIfMissingDbname(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MySQL DSN is missing the database name.');

        $builder = new MysqlPDOBuilder
        ([
            'host'     => 'localhost',
            'username' => 'user',
            'validate' => true,
        ]);

        $builder->validate();
    }

    public function testThrowsIfMissingUsername(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MySQL connection requires a username.');

        $builder = new MysqlPDOBuilder
        ([
            'host'     => 'localhost',
            'dbname'   => 'test',
            'validate' => true,
        ]);

        $builder->validate();
    }

    public function testSetMethodUpdatesProperties(): void
    {
        $builder = new MysqlPDOBuilder();
        $builder->set([
            'host'     => '127.0.0.1',
            'dbname'   => 'mydb',
            'username' => 'admin',
            'password' => 'adminpass',
            'validate' => false,
        ]);

        $this->assertSame('admin', $builder->username);
        $this->assertSame('adminpass', $builder->password);
        $this->assertFalse($builder->validate);
        $this->assertSame('mydb', $builder->dsn->dbname);
    }

    public function testSkipDbNameAllowsMissingDbname(): void
    {
        $builder = new MysqlPDOBuilder
        ([
            'host'        => 'localhost',
            'username'    => 'user',
            'skipDbName'  => true,
            'validate'    => true,
        ]);

        $this->expectNotToPerformAssertions();
        $builder->validate(); // Should not throw
    }

    // Optional: if you want to actually test PDO connection
    // use an in-memory SQLite for demonstration
    public function testInvokeReturnsPdoWhenValidationIsDisabled(): void
    {
        $builder = new DummyPDOBuilder
        ([
            'host'     => '127.0.0.1',
            'dbname'   => 'test',
            'username' => 'root',
            'password' => '',
            'validate' => false,
        ]);

        $pdo = $builder();
        $this->assertInstanceOf(PDO::class , $pdo ) ;
    }
}