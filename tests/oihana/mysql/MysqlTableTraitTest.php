<?php

namespace tests\oihana\mysql;

use PDO;
use PDOException;
use PDOStatement;

use DI\Container;

use InvalidArgumentException;

use PHPUnit\Framework\TestCase;

use oihana\mysql\MysqlModel;

/**
 * Covers {@see \oihana\mysql\traits\MysqlTableTrait}, exercised through
 * {@see MysqlModel} with a mocked PDO connection (no live MySQL server).
 */
class MysqlTableTraitTest extends TestCase
{
    private Container $container ;

    protected function setUp(): void
    {
        $this->container = $this->createStub( Container::class ) ;
    }

    private function model( PDO $pdo ): MysqlModel
    {
        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;
        return $model ;
    }

    // ------------------------------------------------------------------ dropTable

    public function testDropTableExecutes(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "DROP TABLE IF EXISTS `users`" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->dropTable( 'users' ) ) ;
    }

    public function testDropTableReturnsFalseWhenExecFails(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'exec' )->willReturn( false ) ;

        $this->assertFalse( $this->model( $pdo )->dropTable( 'users' ) ) ;
    }

    public function testDropTableRejectsInvalidIdentifier(): void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->model( $this->createStub( PDO::class ) )->dropTable( 'bad-table!' ) ;
    }

    // ------------------------------------------------------------------ listCurrentTables

    public function testListCurrentTablesReturnsNames(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'fetchAll' )->willReturn( [ 'users' , 'orders' ] ) ;

        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'query' )->willReturn( $stmt ) ;

        $this->assertSame( [ 'users' , 'orders' ] , $this->model( $pdo )->listCurrentTables() ) ;
    }

    public function testListCurrentTablesReturnsEmptyWhenQueryFails(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'query' )->willReturn( false ) ;

        $this->assertSame( [] , $this->model( $pdo )->listCurrentTables() ) ;
    }

    public function testListCurrentTablesSwallowsPdoExceptionByDefault(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'query' )->willThrowException( new PDOException( 'boom' ) ) ;

        $this->assertSame( [] , $this->model( $pdo )->listCurrentTables() ) ;
    }

    public function testListCurrentTablesRethrowsWhenThrowable(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'query' )->willThrowException( new PDOException( 'boom' ) ) ;

        $this->expectException( PDOException::class ) ;
        $this->model( $pdo )->listCurrentTables( true ) ;
    }

    // ------------------------------------------------------------------ tableExists

    public function testTableExistsTrue(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchColumn' )->willReturn( 'users' ) ;

        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $stmt ) ;

        $this->assertTrue( $this->model( $pdo )->tableExists( 'users' ) ) ;
    }

    public function testTableExistsFalseWhenNoRow(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchColumn' )->willReturn( false ) ;

        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $stmt ) ;

        $this->assertFalse( $this->model( $pdo )->tableExists( 'ghost' ) ) ;
    }

    public function testTableExistsFalseWhenPrepareFails(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( false ) ;

        $this->assertFalse( $this->model( $pdo )->tableExists( 'users' ) ) ;
    }

    // ------------------------------------------------------------------ getTableSize

    public function testGetTableSizeCastsToInt(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchColumn' )->willReturn( '4096' ) ;

        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $stmt ) ;

        $this->assertSame( 4096 , $this->model( $pdo )->getTableSize( 'users' ) ) ;
    }

    // ------------------------------------------------------------------ optimizeTable

    public function testOptimizeTableExecutes(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "OPTIMIZE TABLE `users`" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->optimizeTable( 'users' ) ) ;
    }

    public function testOptimizeTableReturnsFalseWhenExecFails(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'exec' )->willReturn( false ) ;

        $this->assertFalse( $this->model( $pdo )->optimizeTable( 'users' ) ) ;
    }

    // ------------------------------------------------------------------ renameTable

    public function testRenameTableExecutes(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "RENAME TABLE `users` TO `members`" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->renameTable( 'users' , 'members' ) ) ;
    }

    public function testRenameTableRejectsInvalidTarget(): void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->model( $this->createStub( PDO::class ) )->renameTable( 'users' , 'bad-name!' ) ;
    }

    // ------------------------------------------------------------------ repairTable

    public function testRepairTableExecutes(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "REPAIR TABLE `users`" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->repairTable( 'users' ) ) ;
    }

    public function testRepairTableReturnsFalseWhenExecFails(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'exec' )->willReturn( false ) ;

        $this->assertFalse( $this->model( $pdo )->repairTable( 'users' ) ) ;
    }
}
