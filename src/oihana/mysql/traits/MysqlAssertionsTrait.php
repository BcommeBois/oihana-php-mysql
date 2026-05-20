<?php

namespace oihana\mysql\traits;

use InvalidArgumentException;

/**
 * Provides assertion methods to validate MySQL-related identifiers and hostnames.
 * Ensures that database names, table names, user names, and host strings conform to
 * expected syntax constraints before they are used in queries.
 *
 * Intended to be reused across traits or models that manage MySQL operations.
 *
 * @package oihana\mysql\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait MysqlAssertionsTrait
{
    /**
     * Validates a MySQL identifier such as a database name, user name, or table name.
     *
     * A valid identifier consists of letters (a–z, A–Z), digits (0–9), and underscores (_).
     * This ensures safe usage in SQL queries without risk of injection or syntax errors.
     *
     * @param string $name The identifier to validate.
     *
     * @return void
     *
     * @throws InvalidArgumentException If the identifier contains invalid characters.
     */
    protected function assertIdentifier(string $name): void
    {
        if ( !preg_match('/^[a-zA-Z0-9_]+$/' , $name ) )
        {
            throw new InvalidArgumentException("Invalid identifier: $name" ) ;
        }
    }

    /**
     * Validates a MySQL host string.
     *
     * A valid host string may contain:
     * - letters (a–z, A–Z)
     * - digits (0–9)
     * - dots (.)
     * - hyphens (-)
     * - underscores (_) and percent signs (%) for wildcards
     *
     * @param string $host The host name or IP to validate (e.g., 'localhost', '127.0.0.1', '%.example.com').
     *
     * @return void
     *
     * @throws InvalidArgumentException If the host string contains disallowed characters.
     */
    protected function assertHost(string $host): void
    {
        if ( !preg_match('/^[\w.\-%]+$/' , $host ) )
        {
            throw new InvalidArgumentException("Invalid host: $host" ) ;
        }
    }
}