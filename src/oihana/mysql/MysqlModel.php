<?php

namespace oihana\mysql;

use DI\DependencyException;
use DI\NotFoundException;
use oihana\interfaces\ToAssociativeArray;
use oihana\models\pdo\PDOModel;
use oihana\mysql\traits\MysqlDatabaseTrait;
use oihana\mysql\traits\MysqlPrivilegeTrait;
use oihana\mysql\traits\MysqlTableTrait;
use oihana\mysql\traits\MysqlUserTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

/**
 * MysqlModel provides high-level MySQL administrative operations using PDO.
 *
 * It allows you to:
 * - Create and drop MySQL databases and users.
 * - Grant or revoke privileges.
 * - Inspect privilege assignments (grants).
 * - Validate identifiers and host syntax.
 *
 * Requires a properly connected PDO instance with sufficient privileges.
 *
 * @example
 * ```php
 * $model = new MysqlModel();
 *
 * $model->setPDO( $pdoAdmin ) ; // Connect as root or admin user
 *
 * $model->createDatabase('my_app');
 * $model->createUser('myuser', 'localhost', 'securepass');
 * $model->grantPrivileges('myuser', 'localhost', 'my_app');
 * $model->flushPrivileges();
 *
 * // Rename the user
 * $model->renameUser('myuser', 'localhost', 'user', 'localhost');
 *
 * // Revoke the privilege of the database.
 * $model->revokePrivileges('user', 'localhost', 'myapp');
 *
 * // Export the database informations.
 * print_r( $model->toArray() ) ;
 *
 * if (!$model->databaseExists('myapp'))
 * {
 *    $model->createDatabase('myapp');
 * }
 *
 * if ( !$model->userExists('admin', 'localhost') )
 * {
 *      $model->createUser('admin', 'localhost', 'strongpass');
 * }
 * ```
 *
 * @package oihana\db\mysql
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class MysqlModel extends PDOModel implements ToAssociativeArray
{
    use MysqlDatabaseTrait  ,
        MysqlPrivilegeTrait ,
        MysqlTableTrait     ,
        MysqlUserTrait      ;

    /**
     * @param array $options
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function toArray( array $options = [] ): array
    {
        return
        [
            'databases' => $this->fetchColumnArray("SHOW DATABASES" ) ,
            'users'     => $this->fetch("SELECT User, Host FROM mysql.user" ),
        ];
    }
}