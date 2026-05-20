<?php

namespace oihana\mysql;

use oihana\enums\Char;
use oihana\interfaces\Arrayable;
use function oihana\core\strings\snake;

/**
 * Represents a MySQL DSN (Data Source Name) configuration object.
 *
 * This class provides a convenient and structured way to build MySQL DSN strings
 * used in PDO connections. It supports common connection parameters such as
 * host, port, database name, charset, and optional Unix socket path.
 *
 * @example
 * ```php
 * use oihana\db\mysql\MysqlDSN;
 *
 * $dsn = new MysqlDSN
 * ([
 *     MysqlDSN::HOST        => '127.0.0.1',
 *     MysqlDSN::PORT        => 3306,
 *     MysqlDSN::DBNAME      => 'my_database',
 *     MysqlDSN::CHARSET     => 'utf8mb4',
 *     MysqlDSN::UNIX_SOCKET => '/tmp/mysql.sock',
 * ]);
 *
 * echo (string) $dsn;
 * // Output: mysql:host=127.0.0.1;port=3306;dbname=my_database;charset=utf8mb4;unix_socket=/tmp/mysql.sock
 * ```
 *
 * @package oihana\db\mysql
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class MysqlDSN implements Arrayable
{
    /**
     * Constructs a new MysqlDSN instance.
     *
     * @param array{
     *     charset?    : string|null ,
     *     dbname?     : string|null ,
     *     host?       : string|null ,
     *     port?       : string|null ,
     *     unixSocket? : string|null
     * } $init An associative array of parameters to initialize the DSN.
     *
     * @example
     * ```php
     * $dsn = new MysqlDSN
     * ([
     *     MysqlDSN::DBNAME => 'example_db',
     *     MysqlDSN::HOST   => 'localhost',
     * ]);
     * ```
     */
    public function __construct( array $init = [] )
    {
        $this->charset    = $init[ self::CHARSET     ] ?? 'utf8mb4' ;
        $this->dbname     = $init[ self::DBNAME      ] ?? null ;
        $this->host       = $init[ self::HOST        ] ?? 'localhost' ;
        $this->port       = $init[ self::PORT        ] ?? 3306 ;
        $this->unixSocket = $init[ self::UNIX_SOCKET ] ?? null ;
    }

    /**
     * The 'dbname' parameter.
     */
    const string DBNAME = 'dbname' ;

    /**
     * The 'charset' parameter.
     */
    const string CHARSET = 'charset' ;

    /**
     * The 'host' parameter.
     */
    const string HOST = 'host' ;

    /**
     * The 'port' parameter.
     */
    const string PORT = 'port' ;

    /**
     * The default 'prefix' of the Mysql DSN -> 'mysql:'
     */
    const string PREFIX = 'mysql:' ;

    /**
     * The 'unixSocket' parameter.
     */
    const string UNIX_SOCKET = 'unixSocket' ;

    /**
     * Character set used for the connection.
     * @var string
     */
    public string $charset ;

    /**
     * Name of the database to connect to.
     * @var ?string
     */
    public ?string $dbname ;

    /**
     * Hostname or IP address of the MySQL server.
     * @var string
     */
    public string $host ;

    /**
     * Port number on which the MySQL server is listening.
     * @var int
     */
    public int $port ;

    /**
     * Optional Unix socket path for local MySQL connections.
     * @var ?string
     */
    public ?string $unixSocket ;

    /**
     * Converts the DSN configuration to an associative array.
     *
     * @return array<string, mixed> The configuration as an associative array.
     *
     * @example
     * ```php
     * $dsn = new MysqlDSN([ MysqlDSN::DBNAME => 'test' ]);
     * print_r( $dsn->toArray() ) ;
     * ```
     */
    public function toArray(): array
    {
        return
        [
            self::HOST        => $this->host ,
            self::PORT        => $this->port ,
            self::DBNAME      => $this->dbname ,
            self::CHARSET     => $this->charset ,
            self::UNIX_SOCKET => $this->unixSocket ,
        ];
    }

    /**
     * Builds and returns the DSN string for use with PDO.
     *
     * @return string The DSN string.
     *
     * @example
     * ```php
     * $dsn = new MysqlDSN
     * ([
     *     MysqlDSN::DBNAME  => 'test',
     *     MysqlDSN::HOST    => 'localhost',
     *     MysqlDSN::PORT    => 3307,
     * ]);
     *
     * echo (string) $dsn ;
     * // mysql:host=localhost;port=3307;dbname=test;charset=utf8mb4
     * ```
     */
    public function __toString(): string
    {
        $dsn    = static::PREFIX ;
        $params = [];

        $params[] = static::HOST . Char::EQUAL . $this->host ;
        $params[] = static::PORT . Char::EQUAL . $this->port ;

        if ( $this->dbname !== null )
        {
            $params[] = static::DBNAME . Char::EQUAL . $this->dbname ;
        }

        $params[] = static::CHARSET . Char::EQUAL . $this->charset ;

        if ( $this->unixSocket !== null )
        {
            $params[] = snake( static::UNIX_SOCKET ) . Char::EQUAL . $this->unixSocket ;
        }

        return $dsn . implode( Char::SEMI_COLON, $params );
    }
}