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
 * Covers {@see \oihana\mysql\traits\MysqlUserTrait}, exercised through
 * {@see MysqlModel} with a mocked PDO connection (no live MySQL server).
 */
class MysqlUserTraitTest extends TestCase
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

    /** A PDO stub whose prepare() returns the given statement stub. */
    private function pdoPreparing( PDOStatement|false $statement ): PDO
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willReturn( $statement ) ;
        return $pdo ;
    }

    // ------------------------------------------------------------------ createUser

    public function testCreateUserQuotesPasswordAndExecutes(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->method( 'quote' )->willReturn( "'secret'" ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "CREATE USER IF NOT EXISTS 'bob'@'localhost' IDENTIFIED BY 'secret'" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->createUser( 'bob' , 'localhost' , 'secret' ) ) ;
    }

    public function testCreateUserReturnsFalseWhenExecFails(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'quote' )->willReturn( "''" ) ;
        $pdo->method( 'exec' )->willReturn( false ) ;

        $this->assertFalse( $this->model( $pdo )->createUser( 'bob' ) ) ;
    }

    public function testCreateUserReturnsFalseWhenNoConnection(): void
    {
        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = null ;

        $this->assertFalse( $model->createUser( 'bob' ) ) ;
    }

    public function testCreateUserRejectsInvalidIdentifier(): void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->model( $this->createStub( PDO::class ) )->createUser( 'bad-user!' ) ;
    }

    public function testCreateUserRejectsInvalidHost(): void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->model( $this->createStub( PDO::class ) )->createUser( 'bob' , 'bad host!' ) ;
    }

    // ------------------------------------------------------------------ dropUser

    public function testDropUserExecutes(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "DROP USER IF EXISTS 'bob'@'localhost'" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->dropUser( 'bob' ) ) ;
    }

    public function testDropUserReturnsFalseWhenExecFails(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'exec' )->willReturn( false ) ;

        $this->assertFalse( $this->model( $pdo )->dropUser( 'bob' ) ) ;
    }

    // ------------------------------------------------------------------ getUserInfo

    public function testGetUserInfoReturnsRow(): void
    {
        $row  = [ 'user' => 'bob' , 'host' => 'localhost' , 'plugin' => 'caching_sha2_password' ] ;
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetch' )->willReturn( $row ) ;

        $this->assertSame( $row , $this->model( $this->pdoPreparing( $stmt ) )->getUserInfo( 'bob' ) ) ;
    }

    public function testGetUserInfoReturnsNullWhenPrepareFails(): void
    {
        $this->assertNull( $this->model( $this->pdoPreparing( false ) )->getUserInfo( 'bob' ) ) ;
    }

    public function testGetUserInfoReturnsNullWhenExecuteFails(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( false ) ;

        $this->assertNull( $this->model( $this->pdoPreparing( $stmt ) )->getUserInfo( 'bob' ) ) ;
    }

    public function testGetUserInfoReturnsNullWhenRowMissing(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetch' )->willReturn( false ) ;

        $this->assertNull( $this->model( $this->pdoPreparing( $stmt ) )->getUserInfo( 'ghost' ) ) ;
    }

    public function testGetUserInfoSwallowsPdoExceptionByDefault(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willThrowException( new PDOException( 'boom' ) ) ;

        $this->assertNull( $this->model( $pdo )->getUserInfo( 'bob' ) ) ;
    }

    public function testGetUserInfoRethrowsWhenThrowable(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willThrowException( new PDOException( 'boom' ) ) ;

        $this->expectException( PDOException::class ) ;
        $this->model( $pdo )->getUserInfo( 'bob' , 'localhost' , true ) ;
    }

    // ------------------------------------------------------------------ listUsers

    public function testListUsersReturnsFlatList(): void
    {
        $rows = [ [ 'user' => 'bob' , 'host' => 'localhost' ] , [ 'user' => 'amy' , 'host' => '%' ] ] ;
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchAll' )->willReturn( $rows ) ;

        $this->assertSame( $rows , $this->model( $this->pdoPreparing( $stmt ) )->listUsers() ) ;
    }

    public function testListUsersGroupedByUser(): void
    {
        $rows = [
            [ 'user' => 'bob' , 'host' => 'localhost' ] ,
            [ 'user' => 'bob' , 'host' => '%' ] ,
            [ 'user' => 'amy' , 'host' => 'localhost' ] ,
        ] ;
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchAll' )->willReturn( $rows ) ;

        $this->assertSame(
        [
            'bob' => [ 'localhost' , '%' ] ,
            'amy' => [ 'localhost' ] ,
        ] , $this->model( $this->pdoPreparing( $stmt ) )->listUsers( null , true ) ) ;
    }

    public function testListUsersAppliesLikeFilter(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchAll' )->willReturn( [] ) ;

        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'prepare' )
            ->with( $this->stringContains( 'WHERE User LIKE :like' ) )
            ->willReturn( $stmt ) ;

        $this->assertSame( [] , $this->model( $pdo )->listUsers( 'wp%' ) ) ;
    }

    public function testListUsersReturnsEmptyWhenPrepareFails(): void
    {
        $this->assertSame( [] , $this->model( $this->pdoPreparing( false ) )->listUsers() ) ;
    }

    public function testListUsersSwallowsPdoExceptionByDefault(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willThrowException( new PDOException( 'boom' ) ) ;

        $this->assertSame( [] , $this->model( $pdo )->listUsers() ) ;
    }

    public function testListUsersRethrowsWhenThrowable(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'prepare' )->willThrowException( new PDOException( 'boom' ) ) ;

        $this->expectException( PDOException::class ) ;
        $this->model( $pdo )->listUsers( null , false , true ) ;
    }

    // ------------------------------------------------------------------ renameUser

    public function testRenameUserExecutes(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "RENAME USER 'bob'@'localhost' TO 'bobby'@'%'" )
            ->willReturn( 0 ) ;

        $this->assertTrue( $this->model( $pdo )->renameUser( 'bob' , 'localhost' , 'bobby' , '%' ) ) ;
    }

    public function testRenameUserRejectsInvalidHost(): void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->model( $this->createStub( PDO::class ) )->renameUser( 'bob' , 'bad host!' , 'bobby' , '%' ) ;
    }

    // ------------------------------------------------------------------ userExists

    public function testUserExistsTrue(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchColumn' )->willReturn( '1' ) ;

        $this->assertTrue( $this->model( $this->pdoPreparing( $stmt ) )->userExists( 'bob' ) ) ;
    }

    public function testUserExistsFalseWhenNoRow(): void
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'execute' )->willReturn( true ) ;
        $stmt->method( 'fetchColumn' )->willReturn( false ) ;

        $this->assertFalse( $this->model( $this->pdoPreparing( $stmt ) )->userExists( 'ghost' ) ) ;
    }

    public function testUserExistsFalseWhenPrepareFails(): void
    {
        $this->assertFalse( $this->model( $this->pdoPreparing( false ) )->userExists( 'bob' ) ) ;
    }
}
