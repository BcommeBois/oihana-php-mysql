<?php

namespace oihana\mysql\traits;

use oihana\models\pdo\PDOTrait;
use PDO;
use PDOException;

/**
 * Provides operations for managing and inspecting MySQL tables in the current database.
 * Includes methods for listing, dropping, renaming, optimizing, repairing, and sizing tables.
 *
 * Requires a connected PDO instance and uses `MysqlAssertionsTrait` for input validation.
 *
 * @package oihana\db\mysql\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait MysqlTableTrait
{
    use MysqlAssertionsTrait ,
        PDOTrait ;

    /**
     * Drops a table in the current database.
     *
     * @param string $table Table name.
     * @return bool True on success.
     */
    public function dropTable( string $table ): bool
    {
        $this->assertIdentifier($table);
        $sql = sprintf("DROP TABLE IF EXISTS `%s`" , $table ) ;
        return $this->pdo->exec($sql) !== false;
    }

    /**
     * Lists all tables in the current database.
     *
     * @return array<int, string>  Array of table names.
     */
    public function listCurrentTables( bool $throwable = false ): array
    {
        $query = "SHOW TABLES";

        try
        {
            $stmt = $this->pdo?->query( $query ) ;
            return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN ) : [];
        }
        catch ( PDOException $e )
        {
            if( $throwable )
            {
                throw $e ;
            }
            return [];
        }
    }


    /**
     * Checks if a table exists in the current database.
     *
     * @param string $table Table name.
     * @return bool True if the table exists.
     */
    public function tableExists( string $table ): bool
    {
        $this->assertIdentifier( $table ) ;

        $query = "SHOW TABLES LIKE :table" ;
        $statement  = $this->pdo?->prepare( $query ) ;

        if ( !$statement )
        {
            $statement = null ;
            return false;
        }

        $this->bindValues( $statement , ['table' => $table] ) ;

        $result = $statement->execute() && $statement->fetchColumn() !== false;

        $statement = null ;

        return $result ;
    }
    /**
     * Returns the size of a table in bytes.
     *
     * @param string $table Table name.
     * @return int Table size in bytes.
     */
    public function getTableSize(string $table): int
    {
        $this->assertIdentifier($table);

        $query = "SELECT (DATA_LENGTH + INDEX_LENGTH) AS size 
                  FROM information_schema.TABLES 
                  WHERE TABLE_NAME = :table AND TABLE_SCHEMA = DATABASE()";

        return (int) $this->fetchColumn($query, ['table' => $table]);
    }

    /**
     * Optimizes a table.
     *
     * @param string $table Table name.
     * @return bool True on success.
     */
    public function optimizeTable(string $table): bool
    {
        $this->assertIdentifier($table);
        $sql = sprintf("OPTIMIZE TABLE `%s`", $table ) ;
        return $this->pdo->exec($sql) !== false;
    }

    /**
     * Renames a table in the current database.
     *
     * @param string $from Current table name.
     * @param string $to   New table name.
     * @return bool True if renamed successfully.
     */
    public function renameTable(string $from, string $to): bool
    {
        $this->assertIdentifier($from);
        $this->assertIdentifier($to);

        $sql = sprintf("RENAME TABLE `%s` TO `%s`" , $from , $to ) ;
        return $this->pdo->exec( $sql ) !== false;
    }

    /**
     * Repairs a table.
     *
     * @param string $table Table name.
     * @return bool True on success.
     */
    public function repairTable(string $table): bool
    {
        $this->assertIdentifier($table);
        $sql = sprintf("REPAIR TABLE `%s`" ,  $table ) ;
        return $this->pdo->exec($sql) !== false;
    }
}