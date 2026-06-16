<?php

namespace tests\oihana\mysql;

use PDO;
use PDOStatement;

use DI\Container;

use InvalidArgumentException;

use PHPUnit\Framework\TestCase;

use oihana\mysql\MysqlModel;

/**
 * Covers {@see \oihana\mysql\traits\MysqlDatabaseTrait}, exercised through
 * {@see MysqlModel} with a mocked PDO connection (no live MySQL server).
 */
class MysqlDatabaseTraitTest extends TestCase
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

    /** A statement stub that executes and yields a single-column list via fetchAll(). */
    private function columnStmt( array $values ): PDOStatement
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchAll' )->willReturn( $values ) ;
        return $stmt ;
    }

    // ------------------------------------------------------------------ createDatabase / getRecommendedCollation

    public function testCreateDatabaseUsesGeneralCiForNonUtf8mb4(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "CREATE DATABASE IF NOT EXISTS `mydb` DEFAULT CHARACTER SET latin1 DEFAULT COLLATE latin1_general_ci" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->createDatabase( 'mydb' , 'latin1' ) ) ;
    }

    public function testCreateDatabasePicksMysql8Collation(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->method( 'getAttribute' )->willReturn( '8.0.32' ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "CREATE DATABASE IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_0900_ai_ci" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->createDatabase( 'mydb' ) ) ;
    }

    public function testCreateDatabasePicksMysql57Collation(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->method( 'getAttribute' )->willReturn( '5.7.40' ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "CREATE DATABASE IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_520_ci" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->createDatabase( 'mydb' ) ) ;
    }

    public function testCreateDatabaseFallsBackToUnicodeCollation(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->method( 'getAttribute' )->willReturn( '5.6.0' ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "CREATE DATABASE IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->createDatabase( 'mydb' ) ) ;
    }

    public function testCreateDatabaseHonoursExplicitCollation(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "CREATE DATABASE IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_bin" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->createDatabase( 'mydb' , 'utf8mb4' , 'utf8mb4_bin' ) ) ;
    }

    // ------------------------------------------------------------------ databaseExists

    public function testDatabaseExistsTrue(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchColumn' )->willReturn( 'mydb' ) ;

        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $stmt ) ;

        $this->assertTrue( $this->model( $pdo )->databaseExists( 'mydb' ) ) ;
    }

    public function testDatabaseExistsFalseWhenNoRow(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchColumn' )->willReturn( false ) ;

        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $stmt ) ;

        $this->assertFalse( $this->model( $pdo )->databaseExists( 'ghost' ) ) ;
    }

    public function testDatabaseExistsFalseWhenPrepareFails(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( false ) ;

        $this->assertFalse( $this->model( $pdo )->databaseExists( 'mydb' ) ) ;
    }

    public function testDatabaseExistsRejectsInvalidIdentifier(): void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->model( $this->createStub( PDO::class ) )->databaseExists( 'bad-db!' ) ;
    }

    // ------------------------------------------------------------------ dropDatabase

    public function testDropDatabaseExecutes(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "DROP DATABASE IF EXISTS `mydb`" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->dropDatabase( 'mydb' ) ) ;
    }

    public function testDropDatabaseReturnsFalseWhenExecFails(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'exec' )->willReturn( false ) ;

        $this->assertFalse( $this->model( $pdo )->dropDatabase( 'mydb' ) ) ;
    }

    // ------------------------------------------------------------------ getDatabaseCharset

    public function testGetDatabaseCharsetReturnsCharsetAndCollation(): void
    {
        $row  = [ 'Charset' => 'utf8mb4' , 'Collation' => 'utf8mb4_0900_ai_ci' ] ;
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetch' )->willReturn( $row ) ;

        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $stmt ) ;

        $this->assertSame( $row , $this->model( $pdo )->getDatabaseCharset( 'mydb' ) ) ;
    }

    public function testGetDatabaseCharsetReturnsNullWhenAbsent(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetch' )->willReturn( false ) ;

        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $stmt ) ;

        $this->assertNull( $this->model( $pdo )->getDatabaseCharset( 'mydb' ) ) ;
    }

    // ------------------------------------------------------------------ getDatabaseSize

    public function testGetDatabaseSizeCastsToInt(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchColumn' )->willReturn( '2048' ) ;

        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $stmt ) ;

        $this->assertSame( 2048 , $this->model( $pdo )->getDatabaseSize( 'mydb' ) ) ;
    }

    // ------------------------------------------------------------------ listDatabases

    public function testListDatabasesExcludesSystemByDefault(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $this->columnStmt(
            [ 'mydb' , 'information_schema' , 'mysql' , 'performance_schema' , 'sys' , 'shop' ] ) ) ;

        $this->assertSame( [ 'mydb' , 'shop' ] , $this->model( $pdo )->listDatabases() ) ;
    }

    public function testListDatabasesKeepsSystemWhenRequested(): void
    {
        $all = [ 'mydb' , 'information_schema' , 'mysql' , 'performance_schema' , 'sys' ] ;
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $this->columnStmt( $all ) ) ;

        $this->assertSame( $all , $this->model( $pdo )->listDatabases( false ) ) ;
    }

    // ------------------------------------------------------------------ optimizeDatabase

    public function testOptimizeDatabaseRunsOnEveryTable(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $this->columnStmt( [ 't1' , 't2' ] ) ) ;
        $pdo->method( 'exec' )->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->optimizeDatabase( 'mydb' ) ) ;
    }

    public function testOptimizeDatabaseReturnsFalseOnFailure(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $this->columnStmt( [ 't1' ] ) ) ;
        $pdo->method( 'exec' )->willReturn( false ) ;

        $this->assertFalse( $this->model( $pdo )->optimizeDatabase( 'mydb' ) ) ;
    }

    // ------------------------------------------------------------------ repairDatabase

    public function testRepairDatabaseRunsOnEveryTable(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $this->columnStmt( [ 't1' , 't2' ] ) ) ;
        $pdo->method( 'exec' )->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->repairDatabase( 'mydb' ) ) ;
    }

    public function testRepairDatabaseReturnsFalseOnFailure(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $this->columnStmt( [ 't1' ] ) ) ;
        $pdo->method( 'exec' )->willReturn( false ) ;

        $this->assertFalse( $this->model( $pdo )->repairDatabase( 'mydb' ) ) ;
    }
}
