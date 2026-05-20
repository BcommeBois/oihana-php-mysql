<?php

namespace oihana\mysql\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * MySQL Privilege Constants.
 *
 * This class provides an enumeration of all MySQL privilege keywords,
 * as defined in the official MySQL documentation. It also includes
 * human-readable descriptions for each privilege.
 *
 * These constants represent privilege names that can be used in GRANT,
 * REVOKE, and privilege checking statements. This list is based on MySQL 8.x
 * and covers both global and object-level permissions.
 *
 * Use with {@see ConstantsTrait} to retrieve available privileges dynamically.
 *
 * @example
 * ```php
 * foreach ( MysqlPrivileges::enums() as $privilege)
 * {
 *     echo $privilege . PHP_EOL;
 * }
 *
 * // Check if a privilege is valid
 * if ( MysqlPrivileges::includes('SELECT') )
 * {
 *     echo 'Valid MySQL privilege.' ;
 * }
 *
 * ```
 *
 * @link https://dev.mysql.com/doc/refman/8.0/en/privileges-provided.html MySQL Official Privilege Reference
 */
class MysqlPrivileges
{
    use ConstantsTrait ;

    /** @var string Global privilege granting all other privileges. */
    public const string ALL_PRIVILEGES = 'ALL PRIVILEGES' ;

    // Data manipulation
    public const string DELETE = 'DELETE' ;
    public const string INSERT = 'INSERT' ;
    public const string SELECT = 'SELECT' ;
    public const string UPDATE = 'UPDATE' ;

    // Table and schema
    public const string ALTER       = 'ALTER' ;
    public const string CREATE      = 'CREATE' ;
    public const string DROP        = 'DROP' ;
    public const string INDEX       = 'INDEX' ;
    public const string LOCK_TABLES = 'LOCK TABLES' ;
    public const string REFERENCES  = 'REFERENCES' ;
    public const string TRIGGER     = 'TRIGGER' ;

    // Views and routines
    public const string ALTER_ROUTINE  = 'ALTER ROUTINE' ;
    public const string EXECUTE        = 'EXECUTE' ;
    public const string CREATE_ROUTINE = 'CREATE ROUTINE' ;
    public const string CREATE_VIEW    = 'CREATE VIEW' ;
    public const string SHOW_VIEW      = 'SHOW VIEW' ;

    // Administrative
    public const string CREATE_USER             = 'CREATE USER' ;
    public const string CREATE_TEMPORARY_TABLES = 'CREATE TEMPORARY TABLES' ;
    public const string EVENT                   = 'EVENT' ;
    public const string FILE                    = 'FILE' ;
    public const string GRANT_OPTION            = 'GRANT OPTION' ;
    public const string PROCESS                 = 'PROCESS' ;
    public const string RELOAD                  = 'RELOAD' ;
    public const string SHOW_DATABASES          = 'SHOW DATABASES' ;
    public const string SHUTDOWN                = 'SHUTDOWN' ;
    public const string SUPER                   = 'SUPER' ;

    // Replication
    public const string REPLICATION_CLIENT = 'REPLICATION CLIENT' ;
    public const string REPLICATION_SLAVE  = 'REPLICATION SLAVE' ;

    // Minimal
    public const string USAGE = 'USAGE' ;

    /**
     * Human-readable description for each MySQL privilege.
     *
     * @var array<string, string>
     */
    protected const array DESCRIPTIONS =
    [
        self::ALL_PRIVILEGES          => 'Grants all available privileges.',
        self::SELECT                  => 'Allows reading data using SELECT.',
        self::INSERT                  => 'Allows inserting data into tables.',
        self::UPDATE                  => 'Allows updating existing rows.',
        self::DELETE                  => 'Allows deleting rows from tables.',
        self::CREATE                  => 'Allows creation of new databases and tables.',
        self::DROP                    => 'Allows deletion of databases and tables.',
        self::ALTER                   => 'Allows modifying table structure.',
        self::INDEX                   => 'Allows creating and dropping indexes.',
        self::TRIGGER                 => 'Allows creating and dropping triggers.',
        self::REFERENCES              => 'Allows defining foreign keys.',
        self::LOCK_TABLES             => 'Allows locking tables for access control.',
        self::CREATE_TEMPORARY_TABLES => 'Allows creating temporary tables.',
        self::CREATE_VIEW             => 'Allows creating views.',
        self::SHOW_VIEW               => 'Allows viewing definitions of views.',
        self::CREATE_ROUTINE          => 'Allows creating stored procedures and functions.',
        self::ALTER_ROUTINE           => 'Allows modifying or dropping routines.',
        self::EXECUTE                 => 'Allows executing stored routines.',
        self::EVENT                   => 'Allows creating, altering, dropping events.',
        self::FILE                    => 'Allows reading/writing files on the server.',
        self::PROCESS                 => 'Allows viewing threads from all users.',
        self::RELOAD                  => 'Allows reloading server configuration.',
        self::SHUTDOWN                => 'Allows shutting down the server.',
        self::SUPER                   => 'Grants various powerful administrative privileges.',
        self::SHOW_DATABASES          => 'Allows seeing all databases.',
        self::CREATE_USER             => 'Allows creating, renaming and dropping users.',
        self::GRANT_OPTION            => 'Allows granting privileges to other users.',
        self::REPLICATION_CLIENT      => 'Allows querying replication status.',
        self::REPLICATION_SLAVE       => 'Allows replication slaves to connect.',
        self::USAGE                   => 'Minimal privilege: allows connection only.',
    ];

    /**
     * Get all privileges with their description.
     *
     * @return array<string, string> Array of privilege => description.
     */
    public static function allDescriptions(): array
    {
        return self::DESCRIPTIONS ;
    }

    /**
     * Get a human-readable description for a given privilege.
     *
     * @param string $privilege One of the MysqlPrivileges::* constants.
     * @return string|null Description of the privilege, or null if not defined.
     */
    public static function describe( string $privilege ): ?string
    {
        return self::DESCRIPTIONS[ $privilege ] ?? null ;
    }
}