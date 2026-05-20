<?php

namespace oihana\mysql\traits;

use oihana\models\pdo\PDOTrait;
use PDO;
use PDOException;

/**
 * Provides methods to manage MySQL privileges for users on databases and tables.
 * Includes operations to grant, revoke, inspect, and flush privileges, and parse GRANT statements.
 *
 * Requires a connected PDO instance and uses `MysqlAssertionsTrait` for input validation.
 *
 * @package oihana\db\mysql\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait MysqlPrivilegeTrait
{
    use MysqlAssertionsTrait ,
        PDOTrait ;

    /**
     * Flushes MySQL privileges.
     *
     * This reloads the grant tables to apply recent changes (like CREATE USER, GRANT, etc.).
     *
     * @return bool True on success, false otherwise.
     */
    public function flushPrivileges(): bool
    {
        return $this->pdo->exec( "FLUSH PRIVILEGES" ) !== false ;
    }

    /**
     * Retrieves all GRANT statements for a given user.
     *
     * @param string $username  The MySQL username.
     * @param string $host      The associated host (default: 'localhost').
     * @return array<int, string>  Array of GRANT statements (or empty if none/failure).
     */
    public function getGrants( string $username, string $host = 'localhost' ): array
    {
        $this->assertIdentifier ( $username ) ;
        $this->assertHost       ( $host     ) ;

        $query = sprintf("SHOW GRANTS FOR '%s'@'%s'", $username , $host ) ;

        try
        {
            $statements = $this->pdo?->query( $query );
            return $statements ? $statements->fetchAll( PDO::FETCH_COLUMN ) : [] ;
        }
        catch ( PDOException $e )
        {
            return [] ; // Access denied or unknown user: return empty
        }
    }

    /**
     * Returns a human-readable summary of privileges granted to a user.
     *
     * This method parses and formats the privileges returned by `SHOW GRANTS`
     * into a simple multi-line string, with each line showing the scope
     * (database.table or `ALL`) and the list of privileges.
     *
     * @param string $username  The MySQL username.
     * @param string $host      The host associated with the user (default: 'localhost').
     * @return string           A formatted summary string.
     *
     * @example
     * ```php
     * echo $model->getPrivilegesSummary('user1');
     * // Output:
     * // mydb.*: SELECT, INSERT
     * // mydb.products: SELECT
     * // ALL: USAGE
     * ```
     */
    public function getPrivilegesSummary( string $username, string $host = 'localhost' ): string
    {
        $grants = $this->listPrivileges( $username , $host ) ;

        $out = [];
        foreach ($grants as $scope => $privileges ) {
            $out[] = "$scope: " . implode(', ' , $privileges ) ;
        }

        return implode(PHP_EOL , $out ) ;
    }

    /**
     * Grants all privileges on a database to a user.
     *
     * @param string $username  The user to grant privileges to.
     * @param string $dbname    The database to grant access to.
     * @param string $host      The user's host (usually 'localhost').
     * @return bool             True on success, false otherwise.
     */
    public function grantAllPrivileges( string $username , string $dbname , string $host ): bool
    {
        $this->assertIdentifier ( $dbname   ) ;
        $this->assertIdentifier ( $username ) ;
        $this->assertHost       ( $host     ) ;

        $query  = sprintf( "GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%s'", $dbname , $username , $host ) ;
        $result = $this->pdo->exec( $query ) ;

        if ( $result !== false )
        {
            return $this->flushPrivileges() ;
        }

        return false;
    }

    /**
     * Grants specific privileges on a database or table to a user.
     *
     * @param string      $privileges  A comma-separated list of privileges (e.g. 'SELECT, INSERT').
     * @param string      $dbname      Database name.
     * @param string      $username    The MySQL user.
     * @param string      $host        Host (default: 'localhost').
     * @param string|null $table       Optional table name. If null, grants privileges on the entire database.
     * @return bool                    True if the grant statement executed successfully.
     *
     * @example
     * ```php
     * $model->grantPrivilege('SELECT, INSERT', 'mydb', 'user1');
     * $model->grantPrivilege('UPDATE', 'mydb', 'user1', 'localhost', 'products');
     * ```
     */
    public function grantPrivilege
    (
         string $privileges,
         string $dbname,
         string $username,
         string $host = 'localhost',
        ?string $table = null
    ): bool
    {
        $this->assertIdentifier ( $dbname   ) ;
        $this->assertIdentifier ( $username ) ;
        $this->assertHost       ( $host     ) ;

        if ( $table !== null )
        {
            $this->assertIdentifier( $table ) ;
        }

        $object = $table ? "`$dbname`.`$table`" : "`$dbname`.*";

        $query = sprintf
        (
            'GRANT %s ON %s TO \'%s\'@\'%s\'' ,
            $privileges ,
            $object     ,
            $username   ,
            $host
        );

        return $this->pdo->exec($query) !== false;
    }

    /**
     * Checks whether the given user has ALL PRIVILEGES globally (i.e., ON *.*).
     *
     * @param string $username The MySQL username.
     * @param string $host     The associated host (default: 'localhost').
     * @return bool            True if the user has ALL PRIVILEGES globally.
     */
    public function hasGlobalAllPrivileges( string $username, string $host = 'localhost' ): bool
    {
        $grants = $this->getGrants($username, $host);

        foreach ( $grants as $grant )
        {
            $grant = strtoupper( $grant ) ;
            if ( str_starts_with($grant, 'GRANT ALL PRIVILEGES') && str_contains($grant, ' ON *.* ') )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the given user has ALL PRIVILEGES on a specific database.
     *
     * This method parses the user's GRANT statements and looks for a
     * "GRANT ALL PRIVILEGES ON `database`.* TO ..." entry.
     *
     * @param string $username  The MySQL username to check.
     * @param string $database  The database name to check privileges for.
     * @param string $host      The host part of the MySQL user (default: 'localhost').
     *
     * @return bool             True if the user has ALL PRIVILEGES on the database, false otherwise.
     */
    public function hasAllPrivilegesOnDatabase( string $username, string $database, string $host = 'localhost' ): bool
    {
        $grants  = $this->getGrants( $username , $host ) ;
        $pattern = strtoupper( sprintf( ' ON `%s`.* ' , $database ) ) ;

        foreach ($grants as $grant)
        {
            $grant = strtoupper( $grant ) ;
            if ( str_starts_with( $grant, 'GRANT ALL PRIVILEGES' ) && str_contains( $grant , $pattern ) )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether the user has at least one privilege on a given database or table.
     *
     * @param string      $username  The MySQL user.
     * @param string      $dbname    The database name.
     * @param string|null $table     Optional table name. If null, checks privileges on the database.
     * @param string      $host      The host (default: 'localhost').
     * @return bool                  True if the user has any privilege on the specified scope.
     *
     * @example
     * ```php
     * if ($model->hasAnyPrivilege('user1', 'mydb')) {
     *     echo "User has privileges on the database.";
     * }
     * ```
     */
    public function hasAnyPrivilege
    (
         string $username ,
         string $dbname   ,
        ?string $table = null ,
         string $host = 'localhost'
    ): bool
    {
        $this->assertIdentifier ( $username ) ;
        $this->assertIdentifier ( $dbname   ) ;
        $this->assertHost       ( $host     ) ;

        if ( $table !== null )
        {
            $this->assertIdentifier( $table );
        }

        $scope   = $table ? "{$dbname}.{$table}" : "{$dbname}.*";
        $grants  = $this->listPrivileges( $username , $host ) ;

        return isset( $grants[ $scope ] ) && count( $grants[ $scope ] ) > 0 ;
    }

    /**
     * Checks whether a user has a specific privilege on a database or table.
     *
     * @param string      $username   MySQL username.
     * @param string      $privilege  Privilege to check (e.g. SELECT, INSERT, ALL PRIVILEGES).
     * @param string      $dbname     Database name.
     * @param string|null $table      Optional table name. If null, check DB-level privileges.
     * @param string      $host       Host (default: 'localhost').
     * @return bool                   True if the privilege is granted.
     */
    public function hasPrivilege
    (
        string $username  ,
        string $privilege ,
        string $dbname    ,
       ?string $table = null ,
        string $host = 'localhost'
    ): bool
    {
        $this->assertIdentifier ( $username ) ;
        $this->assertIdentifier ( $dbname   ) ;
        $this->assertHost       ( $host     ) ;

        if ( $table !== null )
        {
            $this->assertIdentifier($table);
        }

        $privilege = strtoupper( $privilege ) ;
        $scope     = $table ? "{$dbname}.{$table}" : "{$dbname}.*";

        $grants = $this->listPrivileges( $username , $host ) ;

        foreach ( [ $scope , 'ALL' ] as $key )
        {
            if (!isset($grants[$key])) {
                continue;
            }

            $privileges = array_map('strtoupper', $grants[$key]);

            if ( in_array('ALL PRIVILEGES' , $privileges , true ) || in_array( $privilege , $privileges , true ) )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Lists all databases on which a user has at least one privilege.
     *
     * This method analyzes the output of `SHOW GRANTS` and extracts database-level
     * or table-level scopes from the privilege definitions. It returns a unique list
     * of database names, including `*` if the user has global privileges.
     *
     * @param string $username  The MySQL username.
     * @param string $host      The host associated with the user (default: 'localhost').
     * @return array<int, string> List of database names (e.g. ['mydb', 'test', '*']).
     *
     * @example
     * ```php
     * $databases = $model->listDatabasesWithPrivileges('user1');
     * // Result: ['mydb', 'test'] or ['*'] if global privileges
     * ```
     */
    public function listDatabasesWithPrivileges( string $username , string $host = 'localhost' ): array
    {
        $privileges = $this->listPrivileges( $username , $host ) ;
        $databases = [];

        foreach ( array_keys( $privileges ) as $scope )
        {
            if ( $scope === 'ALL' )
            {
                $databases[] = '*' ;
            }
            elseif ( preg_match('/^([^\.]+)\./', $scope, $match ) )
            {
                $databases[] = $match[1];
            }
        }

        return array_values( array_unique( $databases ) ) ;
    }

    /**
     * Lists parsed privileges granted to a user.
     *
     * @param string $username  The MySQL user.
     * @param string $host      The associated host (default: 'localhost').
     * @return array<string, array<string>>  [ 'database.table' => [privileges...] ]
     *
     * @example
     * ```php
     * $list = $model->listPrivileges('user1');
     *
     * foreach ( $list as $scope => $rights)
     * {
     *     echo "Privileges on {$scope}:\n - " . implode(', ', $rights) . PHP_EOL ;
     * }
     *
     * // Privileges on mydb.*:
     * // - SELECT, INSERT, UPDATE
     *
     * // Privileges on mydb.products:
     * // - SELECT
     *
     * // Privileges on ALL:
     * // - USAGE
     * ```
     */
    public function listPrivileges( string $username , string $host = 'localhost' ) :array
    {
        $this->assertIdentifier ( $username ) ;
        $this->assertHost       ( $host     ) ;

        $grants  = $this->getGrants( $username , $host ) ;
        $results = [] ;

        foreach ( $grants as $grant )
        {
            if ( preg_match('/^GRANT (.+) ON (.+) TO /i' , $grant , $matches ) )
            {
                $privileges = array_map('trim', explode(',', $matches[1]));
                $target     = $matches[2];

                // Clean the backticks : `mydb`.`mytable` → mydb.mytable
                $target = str_replace( [ '`' , '`.`' ] , [ '' , '.' ] , $target ) ;

                // Normalize *.* -> ALL
                $key = $target === '*.*' ? 'ALL' : $target;

                $results[ $key ] = array_map('strtoupper' ,  $privileges ) ;
            }
        }

        return $results;
    }

    /**
     * Revokes all privileges from a user at the global level (*.*).
     *
     * @param string $username  The MySQL username.
     * @param string $host      The user's host (default: 'localhost').
     * @return bool             True on success, false otherwise.
     *
     * @example
     * ```php
     * $model->revokeAllPrivileges('user1');
     * ```
     */
    public function revokeAllPrivileges( string $username , string $host = 'localhost' ): bool
    {
        $this->assertIdentifier ( $username ) ;
        $this->assertHost       ( $host     ) ;

        $query = sprintf("REVOKE ALL PRIVILEGES ON *.* FROM '%s'@'%s'" , $username , $host ) ;

        return $this->pdo->exec($query) !== false;
    }

    /**
     * Revokes specific privileges from a user on a database or table.
     *
     * @param string              $privileges  Comma-separated privileges (e.g. 'SELECT, INSERT').
     * @param string              $dbname      Database name.
     * @param string              $username    MySQL user.
     * @param string              $host        Host (default: 'localhost').
     * @param string|null         $table       Optional table name (null = whole DB).
     * @return bool                            True if the statement was executed successfully.
     *
     * @example
     * 1. Remove all modification rights:
     * ```php
     * $model->revokePrivilege('INSERT, UPDATE, DELETE', 'mydb', 'user1');
     * ```
     *
     * 2. Only remove SELECT from a specific table
     * ```php
     * $model->revokePrivilege('SELECT', 'mydb', 'user1', 'localhost', 'products');
     * ```
     */
    public function revokePrivilege
    (
        string  $privileges ,
        string  $dbname ,
        string  $username ,
        string  $host  = 'localhost' ,
        ?string $table = null
    ): bool
    {
        $this->assertIdentifier ( $dbname   ) ;
        $this->assertIdentifier ( $username ) ;
        $this->assertHost       ( $host     ) ;

        if ( $table !== null )
        {
            $this->assertIdentifier( $table ) ;
        }

        $object = $table ? sprintf('`%s`.`%s`', $dbname, $table) : sprintf('`%s`.*', $dbname);

        $query = sprintf
        (
            'REVOKE %s ON %s FROM \'%s\'@\'%s\'',
            $privileges,
            $object,
            $username,
            $host
        );

        return $this->pdo->exec( $query ) !== false ;
    }

    /**
     * Revokes all privileges from a user on a specific database.
     *
     * @param string $username  The user to revoke privileges from.
     * @param string $host      The user's host (usually 'localhost').
     * @param string $dbname    The database to revoke access from.
     * @return bool             True on success, false otherwise.
     */
    public function revokePrivileges(string $username, string $host, string $dbname): bool
    {
        $this->assertIdentifier ( $dbname   ) ;
        $this->assertIdentifier ( $username ) ;
        $this->assertHost       ( $host     ) ;

        $query = sprintf( "REVOKE ALL PRIVILEGES ON `%s`.* FROM '%s'@'%s'" , $dbname , $username , $host ) ;

        return $this->pdo->exec( $query ) !== false ;
    }
}