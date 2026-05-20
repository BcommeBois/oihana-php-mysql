<?php

namespace oihana\mysql\traits;

use oihana\models\pdo\PDOTrait;
use PDO;
use PDOException;

/**
 * Provides methods to manage MySQL users using PDO.
 * Includes operations for creating, renaming, deleting, and checking the existence of users.
 *
 * Requires a connected PDO instance and uses `MysqlAssertionsTrait` for input validation.
 *
 * @package oihana\db\mysql\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait MysqlUserTrait
{
    use MysqlAssertionsTrait ,
        PDOTrait ;

    /**
     * Creates a new MySQL user with the given username, host, and password.
     * If the user already exists, the operation has no effect.
     *
     * @param string $username The username to create.
     * @param string $host     The host from which the user connects (default: 'localhost').
     * @param string $password The password for the user.
     *
     * @return bool True on success, false otherwise.
     */
    public function createUser( string $username , string $host = 'localhost' , string $password = '' ): bool
    {
        $this->assertIdentifier ( $username ) ;
        $this->assertHost       ( $host     ) ;
        if( $this->pdo !== null )
        {
            $password = $this->pdo->quote( $password ) ;
            $query    = "CREATE USER IF NOT EXISTS '{$username}'@'{$host}' IDENTIFIED BY {$password}";
            return $this->pdo->exec($query) !== false;
        }
        return false ;
    }

    /**
     * Drops a MySQL user if it exists.
     *
     * @param string $username  The username to drop.
     * @param string $host      The host (default: 'localhost').
     * @return bool             True on success, false otherwise.
     */
    public function dropUser( string $username , string $host = 'localhost' ): bool
    {
        $this->assertIdentifier ( $username ) ;
        $this->assertHost       ( $host     ) ;
        return $this->pdo?->exec( sprintf("DROP USER IF EXISTS '%s'@'%s'" , $username , $host ) ) !== false;
    }

    /**
     * Retrieves complete information about a MySQL user.
     *
     * @param string $username The username to get information for.
     * @param string $host     The host (default: 'localhost').
     * @param bool   $throwable Indicates if the method should throw exceptions.
     *
     * @return array|null Returns user information array or null if user doesn't exist.
     *                   Array contains: user, host, password_expired, account_locked,
     *                   password_last_changed, password_lifetime, max_connections,
     *                   max_questions, max_updates, max_user_connections, plugin,
     *                   authentication_string, ssl_type, ssl_cipher, x509_issuer,
     *                   x509_subject, and grants.
     */
    public function getUserInfo( string $username, string $host = 'localhost', bool $throwable = false ): ?array
    {
        $this->assertIdentifier ( $username ) ;
        $this->assertHost       ( $host     ) ;

        try
        {
            $query = "
            SELECT 
                User as user,
                Host as host,
                password_expired,
                account_locked,
                password_last_changed,
                password_lifetime,
                max_connections,
                max_questions,
                max_updates,
                max_user_connections,
                plugin,
                authentication_string,
                ssl_type,
                ssl_cipher,
                x509_issuer,
                x509_subject
            FROM mysql.user 
            WHERE User = :user AND Host = :host
            ";

            $statement = $this->pdo?->prepare($query);

            if (!$statement) {
                return null;
            }

            $this->bindValues($statement, ['user' => $username, 'host' => $host] ) ;

            if ( !$statement->execute() )
            {
                return null;
            }

            $userInfo = $statement->fetch(PDO::FETCH_ASSOC ) ;

            if ( !$userInfo )
            {
                return null ;
            }

            // // Récupération des privilèges (grants)
            // $userInfo['grants'] = $this->getUserGrants($username, $host, $throwable);
            //
            // // Récupération des rôles (MySQL 8.0+)
            // $userInfo['roles'] = $this->getUserRoles($username, $host, $throwable);

            return $userInfo;

        }
        catch ( PDOException $exception )
        {
            if ( $throwable )
            {
                throw $exception ;
            }
            return null ;
        }
    }

    /**
     * Returns a list of MySQL users with their associated hosts.
     *
     * @param string|null $like      Optional SQL pattern to filter users (e.g. 'wp%').
     * @param bool        $grouped   Whether to group hosts under each username.
     * @param bool        $throwable Indicates if the method is throwable.
     *
     * @return array  If grouped, returns array<string, string[]> (user => [hosts]).
     *               Otherwise, returns array<int, array{user: string, host: string}>.
     */
    public function listUsers( ?string $like = null, bool $grouped = false , bool $throwable = false ): array
    {
        $params = [];

        $query = "SELECT User AS user, Host AS host FROM mysql.user" ;

        if ( isset( $like ) )
        {
            $query .= " WHERE User LIKE :like" ;
        }

        $query .= " ORDER BY User, Host" ;

        try
        {

            $statement = $this->pdo?->prepare( $query ) ;

            if ( !$statement )
            {
                return [] ;
            }

            $statement->execute( $params );

            $results   = $statement->fetchAll( PDO::FETCH_ASSOC ) ?: [] ;
            $statement = null ;

            if ( !$grouped )
            {
                return $results;
            }

            $groups = [] ;

            foreach ( $results as $row )
            {
                $user = $row[ 'user' ] ;
                $host = $row[ 'host' ] ;
                $groups[ $user ][] = $host ;
            }

            return $groups ;
        }
        catch ( PDOException $exception )
        {
            if( $throwable )
            {
                throw $exception ;
            }
            return [] ;
        }
    }

    /**
     * Renames an existing MySQL user.
     *
     * @param string $fromUser Current username.
     * @param string $fromHost Current host.
     * @param string $toUser   New username.
     * @param string $toHost   New host.
     *
     * @return bool True if the rename operation was successful, false otherwise.
     */
    public function renameUser( string $fromUser , string $fromHost , string $toUser , string $toHost ): bool
    {
        $this->assertIdentifier ( $fromUser ) ;
        $this->assertIdentifier ( $toUser   ) ;
        $this->assertHost       ( $fromHost ) ;
        $this->assertHost       ( $toHost   ) ;

        $query = sprintf( "RENAME USER '%s'@'%s' TO '%s'@'%s'" , $fromUser , $fromHost , $toUser , $toHost );

        return $this->pdo->exec( $query ) !== false ;
    }

    /**
     * Checks if a MySQL user exists.
     *
     * @param string $username  Username to check.
     * @param string $host      Host (default: 'localhost').
     * @return bool              True if the user exists.
     */
    public function userExists( string $username , string $host = 'localhost' ): bool
    {
        $this->assertIdentifier( $username ) ;
        $this->assertHost( $host );

        $query     = "SELECT 1 FROM mysql.user WHERE User = :user AND Host = :host" ;
        $statement = $this->pdo?->prepare( $query );

        if (! $statement )
        {
            return false ;
        }

        $this->bindValues( $statement , [ 'user' => $username , 'host' => $host ] );

        return $statement->execute() && $statement->fetchColumn() !== false ;
    }
}