<?php

namespace tests\oihana\mysql;

use PDO;
use PDOStatement;

use DI\Container;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use oihana\mysql\MysqlModel;

class MysqlModelTest extends TestCase
{
    private ?MysqlModel $model = null ;
    private ?Stub $pdo   = null ;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->pdo = $this->createStub(PDO::class);
        $container = $this->createStub(Container::class);

        $this->model = new MysqlModel( $container , [ 'pdo' => $this->pdo ] );
    }

    public function testCreateDatabaseReturnsTrueOnSuccess()
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('exec')
            ->with($this->stringContains('CREATE DATABASE'))
            ->willReturn(1); // exec retourne le nb de lignes affectées ou true

        $this->model->pdo = $pdo;

        $result = $this->model->createDatabase('testdb');
        $this->assertTrue($result);
    }

    public function testCreateDatabaseReturnsFalseOnFailure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid identifier');

        $this->model->createDatabase('invalid-db-name!');
    }

    public function testToArrayReturnsDatabasesAndUsers(): void
    {
        $stmt = $this->createStub(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn(['app', 'shop']);
        $stmt->method('fetch')->willReturn(['User' => 'root', 'Host' => 'localhost']);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);
        $this->model->pdo = $pdo;

        $array = $this->model->toArray();

        $this->assertSame(['app', 'shop'], $array['databases']);
        $this->assertEquals((object) ['User' => 'root', 'Host' => 'localhost'], $array['users']);
    }
}