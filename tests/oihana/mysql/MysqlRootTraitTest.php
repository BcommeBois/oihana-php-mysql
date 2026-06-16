<?php

namespace tests\oihana\mysql;

use PDO;

use DI\Container;

use PHPUnit\Framework\TestCase;

use oihana\mysql\enums\MysqlParam;
use oihana\mysql\MysqlModel;
use oihana\mysql\traits\MysqlRootTrait;

/** Minimal host exposing {@see MysqlRootTrait} for isolated testing. */
class RootTraitHost
{
    use MysqlRootTrait ;
}

/**
 * Covers {@see \oihana\mysql\traits\MysqlRootTrait::initializeMysqlRoot}.
 */
class MysqlRootTraitTest extends TestCase
{
    private function mysqlModel(): MysqlModel
    {
        return new MysqlModel( $this->createStub( Container::class ) , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
    }

    public function testInitializeWithDirectInstance(): void
    {
        $model = $this->mysqlModel() ;

        $host = new RootTraitHost() ;
        $host->container = $this->createStub( Container::class ) ;
        $host->initializeMysqlRoot( [ MysqlParam::MYSQL_ROOT => $model ] ) ;

        $this->assertSame( $model , $host->mysqlRoot ) ;
    }

    public function testInitializeResolvesServiceNameFromContainer(): void
    {
        $model = $this->mysqlModel() ;

        $container = $this->createStub( Container::class ) ;
        $container->method( 'has' )->willReturn( true ) ;
        $container->method( 'get' )->willReturn( $model ) ;

        $host = new RootTraitHost() ;
        $host->container = $container ;
        $host->initializeMysqlRoot( [ MysqlParam::MYSQL_ROOT => 'mysql.root' ] ) ;

        $this->assertSame( $model , $host->mysqlRoot ) ;
    }

    public function testInitializeFallsBackToNullForUnresolvedReference(): void
    {
        $container = $this->createStub( Container::class ) ;
        $container->method( 'has' )->willReturn( false ) ;

        $host = new RootTraitHost() ;
        $host->container = $container ;
        $host->initializeMysqlRoot( [ MysqlParam::MYSQL_ROOT => 'unknown.service' ] ) ;

        $this->assertNull( $host->mysqlRoot ) ;
    }

    public function testInitializeReturnsSelf(): void
    {
        $host = new RootTraitHost() ;
        $host->container = $this->createStub( Container::class ) ;

        $this->assertSame( $host , $host->initializeMysqlRoot() ) ;
    }
}
