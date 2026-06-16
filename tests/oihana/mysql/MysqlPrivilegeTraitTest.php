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
 * Covers {@see \oihana\mysql\traits\MysqlPrivilegeTrait}, exercised through
 * {@see MysqlModel} with a mocked PDO connection (no live MySQL server).
 */
class MysqlPrivilegeTraitTest extends TestCase
{
    private Container $container ;

    protected function setUp(): void
    {
        $this->container = $this->createStub( Container::class ) ;
    }

    /**
     * Builds a model whose PDO::query() returns a statement yielding $grants.
     *
     * @param array<int,string> $grants Raw SHOW GRANTS rows.
     */
    private function modelWithGrants( array $grants ): MysqlModel
    {
        $stmt = $this->createStub( PDOStatement::class ) ;
        $stmt->method( 'fetchAll' )->willReturn( $grants ) ;

        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'query' )->willReturn( $stmt ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        return $model ;
    }

    // ------------------------------------------------------------------ flushPrivileges

    public function testFlushPrivilegesReturnsTrueOnSuccess(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( 'FLUSH PRIVILEGES' )
            ->willReturn( 0 ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertTrue( $model->flushPrivileges() ) ;
    }

    public function testFlushPrivilegesReturnsFalseOnFailure(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'exec' )->willReturn( false ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertFalse( $model->flushPrivileges() ) ;
    }

    // ------------------------------------------------------------------ getGrants

    public function testGetGrantsReturnsRows(): void
    {
        $grants = [ "GRANT SELECT ON `mydb`.* TO 'user1'@'localhost'" ] ;
        $model  = $this->modelWithGrants( $grants ) ;

        $this->assertSame( $grants , $model->getGrants( 'user1' ) ) ;
    }

    public function testGetGrantsReturnsEmptyArrayWhenQueryReturnsFalse(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'query' )->willReturn( false ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertSame( [] , $model->getGrants( 'user1' ) ) ;
    }

    public function testGetGrantsReturnsEmptyArrayOnPdoException(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'query' )->willThrowException( new PDOException( 'Access denied' ) ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertSame( [] , $model->getGrants( 'unknown' ) ) ;
    }

    public function testGetGrantsRejectsInvalidIdentifier(): void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->modelWithGrants( [] )->getGrants( 'bad-user!' ) ;
    }

    public function testGetGrantsRejectsInvalidHost(): void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->modelWithGrants( [] )->getGrants( 'user1' , 'bad host!' ) ;
    }

    // ------------------------------------------------------------------ listPrivileges

    public function testListPrivilegesParsesScopesAndBackticks(): void
    {
        $model = $this->modelWithGrants(
        [
            "GRANT SELECT, INSERT ON `mydb`.* TO 'user1'@'localhost'" ,
            "GRANT SELECT ON `mydb`.`products` TO 'user1'@'localhost'" ,
            "GRANT USAGE ON *.* TO 'user1'@'localhost'" ,
        ] ) ;

        $this->assertSame(
        [
            'mydb.*'        => [ 'SELECT' , 'INSERT' ] ,
            'mydb.products' => [ 'SELECT' ] ,
            'ALL'           => [ 'USAGE' ] ,
        ] , $model->listPrivileges( 'user1' ) ) ;
    }

    public function testListPrivilegesIgnoresUnparsableRows(): void
    {
        $model = $this->modelWithGrants( [ 'NOT A GRANT STATEMENT' ] ) ;
        $this->assertSame( [] , $model->listPrivileges( 'user1' ) ) ;
    }

    // ------------------------------------------------------------------ getPrivilegesSummary

    public function testGetPrivilegesSummaryFormatsScopes(): void
    {
        $model = $this->modelWithGrants(
        [
            "GRANT SELECT, INSERT ON `mydb`.* TO 'user1'@'localhost'" ,
            "GRANT USAGE ON *.* TO 'user1'@'localhost'" ,
        ] ) ;

        $expected = 'mydb.*: SELECT, INSERT' . PHP_EOL . 'ALL: USAGE' ;
        $this->assertSame( $expected , $model->getPrivilegesSummary( 'user1' ) ) ;
    }

    // ------------------------------------------------------------------ grantAllPrivileges

    public function testGrantAllPrivilegesSuccessFlushes(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'exec' )->willReturn( 0 ) ; // grant + flush

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertTrue( $model->grantAllPrivileges( 'user1' , 'mydb' , 'localhost' ) ) ;
    }

    public function testGrantAllPrivilegesReturnsFalseWhenExecFails(): void
    {
        $pdo = $this->createStub( PDO::class ) ;
        $pdo->method( 'exec' )->willReturn( false ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertFalse( $model->grantAllPrivileges( 'user1' , 'mydb' , 'localhost' ) ) ;
    }

    // ------------------------------------------------------------------ grantPrivilege

    public function testGrantPrivilegeOnDatabase(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "GRANT SELECT, INSERT ON `mydb`.* TO 'user1'@'localhost'" )
            ->willReturn( 0 ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertTrue( $model->grantPrivilege( 'SELECT, INSERT' , 'mydb' , 'user1' ) ) ;
    }

    public function testGrantPrivilegeOnTable(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "GRANT UPDATE ON `mydb`.`products` TO 'user1'@'localhost'" )
            ->willReturn( 0 ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertTrue( $model->grantPrivilege( 'UPDATE' , 'mydb' , 'user1' , 'localhost' , 'products' ) ) ;
    }

    public function testGrantPrivilegeRejectsInvalidTable(): void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->modelWithGrants( [] )->grantPrivilege( 'SELECT' , 'mydb' , 'user1' , 'localhost' , 'bad table!' ) ;
    }

    // ------------------------------------------------------------------ hasGlobalAllPrivileges

    public function testHasGlobalAllPrivilegesTrue(): void
    {
        $model = $this->modelWithGrants( [ "GRANT ALL PRIVILEGES ON *.* TO 'user1'@'localhost'" ] ) ;
        $this->assertTrue( $model->hasGlobalAllPrivileges( 'user1' ) ) ;
    }

    public function testHasGlobalAllPrivilegesFalse(): void
    {
        $model = $this->modelWithGrants( [ "GRANT SELECT ON `mydb`.* TO 'user1'@'localhost'" ] ) ;
        $this->assertFalse( $model->hasGlobalAllPrivileges( 'user1' ) ) ;
    }

    // ------------------------------------------------------------------ hasAllPrivilegesOnDatabase

    public function testHasAllPrivilegesOnDatabaseTrue(): void
    {
        $model = $this->modelWithGrants( [ "GRANT ALL PRIVILEGES ON `mydb`.* TO 'user1'@'localhost'" ] ) ;
        $this->assertTrue( $model->hasAllPrivilegesOnDatabase( 'user1' , 'mydb' ) ) ;
    }

    public function testHasAllPrivilegesOnDatabaseFalse(): void
    {
        $model = $this->modelWithGrants( [ "GRANT SELECT ON `mydb`.* TO 'user1'@'localhost'" ] ) ;
        $this->assertFalse( $model->hasAllPrivilegesOnDatabase( 'user1' , 'mydb' ) ) ;
    }

    // ------------------------------------------------------------------ hasAnyPrivilege

    public function testHasAnyPrivilegeOnDatabaseTrue(): void
    {
        $model = $this->modelWithGrants( [ "GRANT SELECT ON `mydb`.* TO 'user1'@'localhost'" ] ) ;
        $this->assertTrue( $model->hasAnyPrivilege( 'user1' , 'mydb' ) ) ;
    }

    public function testHasAnyPrivilegeOnTableTrue(): void
    {
        $model = $this->modelWithGrants( [ "GRANT SELECT ON `mydb`.`products` TO 'user1'@'localhost'" ] ) ;
        $this->assertTrue( $model->hasAnyPrivilege( 'user1' , 'mydb' , 'products' ) ) ;
    }

    public function testHasAnyPrivilegeFalseWhenScopeAbsent(): void
    {
        $model = $this->modelWithGrants( [ "GRANT SELECT ON `other`.* TO 'user1'@'localhost'" ] ) ;
        $this->assertFalse( $model->hasAnyPrivilege( 'user1' , 'mydb' ) ) ;
    }

    // ------------------------------------------------------------------ hasPrivilege

    public function testHasPrivilegeMatchesSpecificPrivilege(): void
    {
        $model = $this->modelWithGrants( [ "GRANT SELECT, INSERT ON `mydb`.* TO 'user1'@'localhost'" ] ) ;
        $this->assertTrue( $model->hasPrivilege( 'user1' , 'select' , 'mydb' ) ) ;
    }

    public function testHasPrivilegeMatchesAllPrivileges(): void
    {
        $model = $this->modelWithGrants( [ "GRANT ALL PRIVILEGES ON `mydb`.* TO 'user1'@'localhost'" ] ) ;
        $this->assertTrue( $model->hasPrivilege( 'user1' , 'DELETE' , 'mydb' ) ) ;
    }

    public function testHasPrivilegeMatchesViaAllScope(): void
    {
        $model = $this->modelWithGrants( [ "GRANT USAGE ON *.* TO 'user1'@'localhost'" ] ) ;
        $this->assertTrue( $model->hasPrivilege( 'user1' , 'USAGE' , 'mydb' ) ) ;
    }

    public function testHasPrivilegeFalseWhenAbsent(): void
    {
        $model = $this->modelWithGrants( [ "GRANT SELECT ON `mydb`.* TO 'user1'@'localhost'" ] ) ;
        $this->assertFalse( $model->hasPrivilege( 'user1' , 'DELETE' , 'mydb' , 'products' ) ) ;
    }

    // ------------------------------------------------------------------ listDatabasesWithPrivileges

    public function testListDatabasesWithPrivileges(): void
    {
        $model = $this->modelWithGrants(
        [
            "GRANT SELECT ON `mydb`.* TO 'user1'@'localhost'" ,
            "GRANT SELECT ON `mydb`.`products` TO 'user1'@'localhost'" ,
            "GRANT USAGE ON *.* TO 'user1'@'localhost'" ,
        ] ) ;

        $this->assertSame( [ 'mydb' , '*' ] , $model->listDatabasesWithPrivileges( 'user1' ) ) ;
    }

    // ------------------------------------------------------------------ revokeAllPrivileges

    public function testRevokeAllPrivileges(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "REVOKE ALL PRIVILEGES ON *.* FROM 'user1'@'localhost'" )
            ->willReturn( 0 ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertTrue( $model->revokeAllPrivileges( 'user1' ) ) ;
    }

    // ------------------------------------------------------------------ revokePrivilege

    public function testRevokePrivilegeOnDatabase(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "REVOKE INSERT, UPDATE ON `mydb`.* FROM 'user1'@'localhost'" )
            ->willReturn( 0 ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertTrue( $model->revokePrivilege( 'INSERT, UPDATE' , 'mydb' , 'user1' ) ) ;
    }

    public function testRevokePrivilegeOnTable(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "REVOKE SELECT ON `mydb`.`products` FROM 'user1'@'localhost'" )
            ->willReturn( 0 ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertTrue( $model->revokePrivilege( 'SELECT' , 'mydb' , 'user1' , 'localhost' , 'products' ) ) ;
    }

    // ------------------------------------------------------------------ revokePrivileges

    public function testRevokePrivileges(): void
    {
        $pdo = $this->createMock( PDO::class ) ;
        $pdo->expects( $this->once() )
            ->method( 'exec' )
            ->with( "REVOKE ALL PRIVILEGES ON `mydb`.* FROM 'user1'@'localhost'" )
            ->willReturn( 0 ) ;

        $model = new MysqlModel( $this->container , [ 'pdo' => $this->createStub( PDO::class ) ] ) ;
        $model->pdo = $pdo ;

        $this->assertTrue( $model->revokePrivileges( 'user1' , 'localhost' , 'mydb' ) ) ;
    }
}
