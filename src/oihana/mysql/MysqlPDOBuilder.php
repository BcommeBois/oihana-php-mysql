<?php

namespace oihana\mysql;

use InvalidArgumentException;
use oihana\enums\Char;
use oihana\interfaces\Arrayable;
use oihana\traits\ToStringTrait;
use PDO;

/**
 * Builds and configures a PDO connection to a MySQL database.
 *
 * This class simplifies the creation of a configured `PDO` instance by wrapping
 * the DSN, authentication credentials, options, and validation logic.
 *
 * @example
 * Basic usage:
 * ```php
 * use oihana\db\mysql\MysqlPDOBuilder;
 *
 * $pdoBuilder = new MysqlPDOBuilder([
 *     'host'     => 'localhost',
 *     'dbname'   => 'test_db',
 *     'username' => 'root',
 *     'password' => 'secret',
 * ]);
 *
 * $pdo = $pdoBuilder(); // Returns a configured PDO instance
 * ```
 *
 * Skipping database validation:
 * ```php
 * $pdoBuilder = new MysqlPDOBuilder([
 *     'host'         => 'localhost',
 *     'username'     => 'admin',
 *     'skipDbName'   => true,
 *     'validate'     => false,
 * ]);
 *
 * $pdo = $pdoBuilder(); // No validation performed
 * ```
 *
 * @package oihana\db\mysql
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class MysqlPDOBuilder implements Arrayable
{
    /**
     * Initializes the builder with given configuration.
     *
     * @param array{
     *     charset?    : string|null ,
     *     dbname?     : string|null ,
     *     host?       : string|null ,
     *     options?    : array|null  ,
     *     password?   : string|null ,
     *     port?       : string|null ,
     *     skipDbName? : bool|null   ,
     *     unixSocket? : string|null ,
     *     username?   : string|null ,
     *     validate?   : bool|null
     * } $init Associative array of connection parameters:
     * - `host`, `port`, `dbname`, `charset`, `unixSocket` (for DSN)
     * - `username`, `password` (for credentials)
     * - `options` (PDO options)
     * - `skipDbName` (bool)
     * - `validate` (bool)
     *
     * @example
     * ```php
     * $builder = new MysqlPDOBuilder([
     *     'host'     => '127.0.0.1',
     *     'dbname'   => 'shop',
     *     'username' => 'admin',
     *     'password' => '1234',
     * ]);
     * ```
     */
    public function __construct( array $init = [] )
    {
        $this->set( $init ) ;
    }

    public const string OPTIONS      = 'options' ;
    public const string PASSWORD     = 'password' ;
    public const string SKIP_DB_NAME = 'skipDbName' ;
    public const string USERNAME     = 'username' ;
    public const string VALIDATE     = 'validate' ;

    public const array DEFAULT_OPTIONS =
    [
        PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC ,
        PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION ,
        PDO::ATTR_PERSISTENT          => true,
        PDO::ATTR_EMULATE_PREPARES    => false,
        PDO::ATTR_STRINGIFY_FETCHES   => false,
    ];

    use ToStringTrait ;

    /**
     * The DSN configuration object.
     * @var MysqlDSN
     */
    public MysqlDSN $dsn ;

    /**
     * Additional PDO options.
     * @var array
     */
    public array $options = [];

    /**
     * The user password for connection.
     * @var ?string
     */
    public ?string $password ;

    /**
     * Whether to skip validation of the database name.
     * @var bool
     */
    public bool $skipDbName = false ;

    /**
     * Username used for the PDO connection.
     * @var ?string
     */
    public ?string $username ;

    /**
     * Whether to perform validation before establishing connection.
     * @var bool
     */
    public bool $validate = true ;

    /**
     * Creates and returns a new PDO instance.
     *
     * @return ?PDO The PDO connection, or null if validation fails.
     *
     * @throws InvalidArgumentException If validation fails.
     *
     * @example
     * ```php
     * $builder = new MysqlPDOBuilder
     * ([
     *     'host'     => 'localhost',
     *     'dbname'   => 'demo',
     *     'username' => 'root',
     *     'password' => 'root',
     * ]);
     *
     * $pdo = $builder();
     * ```
     */
    public function __invoke(): ?PDO
    {
        if ( $this->validate )
        {
            $this->validate();
        }
        return $this->createPDO( (string) $this->dsn , $this->username , $this->password , $this->options ) ;
    }

    /**
     * The internal PDO maker method.
     * @param string $dsn
     * @param string|null $username
     * @param string|null $password
     * @param array $options
     * @return PDO
     */
    protected function createPDO( string $dsn, ?string $username , ?string $password , array $options ): PDO
    {
        return new PDO( $dsn , $username , $password , $options ) ;
    }

    /**
     * Sets the builder with given configuration.
     *
     * @param array{
     *     charset?    : string|null ,
     *     dbname?     : string|null ,
     *     host?       : string|null ,
     *     options?    : array|null  ,
     *     password?   : string|null ,
     *     port?       : string|null ,
     *     skipDbName? : bool|null   ,
     *     unixSocket? : string|null ,
     *     username?   : string|null ,
     *     validate?   : bool|null
     * } $init Associative array of connection parameters:
     * - `host`, `port`, `dbname`, `charset`, `unixSocket` (for DSN)
     * - `username`, `password` (for credentials)
     * - `options` (PDO options)
     * - `skipDbName` (bool)
     * - `validate` (bool)
     *
     * @example
     * ```php
     * $builder = new MysqlPDOBuilder();
     *
     * $builder->set
     * ([
     *      'host'     => '127.0.0.1',
     *      'dbname'   => 'shop',
     *      'username' => 'admin',
     *      'password' => '1234',
     *  ]);
     * ```
     */
    public function set( array $init = [] ):void
    {
        $this->dsn        = new MysqlDSN( $init ) ;
        $this->options    = array_replace(self::DEFAULT_OPTIONS , $init[ self::OPTIONS ] ?? [] );
        $this->password   = $init[ self::PASSWORD ] ?? null ;
        $this->skipDbName = $init[ self::SKIP_DB_NAME ] ?? false ;
        $this->username   = $init[ self::USERNAME ] ?? null ;
        $this->validate   = $init[ self::VALIDATE ] ?? true ;
    }

    /**
     * Returns the full configuration as array (dsn + credentials + options).
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return
        [
            'dsn'      => $this->dsn->toArray(),
            'username' => $this->username,
            'password' => str_repeat(Char::ASTERISK , strlen( ( string ) $this->password ) ), // Hidden
            'options'  => $this->options ,
            'validate' => $this->validate ,
        ];
    }

    /**
     * Validates required DSN fields.
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        if ( !$this->dsn->host )
        {
            throw new InvalidArgumentException('MySQL DSN is missing the host.' ) ;
        }

        if ( !$this->dsn->dbname && !$this->skipDbName )
        {
            throw new InvalidArgumentException('MySQL DSN is missing the database name.' ) ;
        }

        if ( !$this->username )
        {
            throw new InvalidArgumentException('MySQL connection requires a username.' ) ;
        }
    }
}