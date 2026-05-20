<?php

namespace tests\oihana\mysql;

use PDO;

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
}