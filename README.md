# Oihana PHP - Mysql

![Oihana PHP Mysql](https://raw.githubusercontent.com/BcommeBois/oihana-php-mysql/main/assets/images/oihana-php-mysql-logo-inline-512x160.png)

MySQL utilities for PHP 8.4+: DSN builder, robust PDO connection builder, and a high-level admin model for managing databases, users, privileges and tables.

[![Latest Version](https://img.shields.io/packagist/v/oihana/php-mysql.svg?style=flat-square)](https://packagist.org/packages/oihana/php-mysql)  
[![Total Downloads](https://img.shields.io/packagist/dt/oihana/php-mysql.svg?style=flat-square)](https://packagist.org/packages/oihana/php-mysql)  
[![License](https://img.shields.io/packagist/l/oihana/php-mysql.svg?style=flat-square)](LICENSE)

## 📚 Documentation

Full project documentation is available at:  
👉 https://bcommebois.github.io/oihana-php-mysql

## 📦 Installation

> Requires [PHP 8.4+](https://php.net/releases/) and `ext-pdo`

Install via Composer:
```bash
composer require oihana/php-mysql
```

## ✨ Features

- **`MysqlDSN`** – build a MySQL DSN string from a structured configuration (host, port, dbname, charset, unix socket).
- **`MysqlPDOBuilder`** – create a configured `PDO` instance with sane defaults, optional validation, and easy override of options.
- **`MysqlModel`** – high-level administrative model on top of PDO to manage databases, users, privileges and tables.
- **Traits** – reusable building blocks (`MysqlDatabaseTrait`, `MysqlUserTrait`, `MysqlPrivilegeTrait`, `MysqlTableTrait`, `MysqlAssertionsTrait`, `MysqlRootTrait`).
- **Enums** – `MysqlParam`, `MysqlPrivileges` for strongly-typed configuration keys and privilege names.

## 🚀 Quick start

```php
require __DIR__ . '/vendor/autoload.php';

use oihana\mysql\MysqlPDOBuilder;
use oihana\mysql\MysqlModel;

// Build a PDO connection
$pdo = (new MysqlPDOBuilder([
    'host'     => '127.0.0.1',
    'dbname'   => 'demo',
    'username' => 'root',
    'password' => 'secret',
]))();

// Use the admin model
$model = new MysqlModel();
$model->setPDO( $pdo );

if ( !$model->databaseExists('my_app') )
{
    $model->createDatabase('my_app');
}
```

## 🧰 Usage

### Build a DSN

```php
use oihana\mysql\MysqlDSN;

$dsn = new MysqlDSN([
    MysqlDSN::HOST        => '127.0.0.1',
    MysqlDSN::PORT        => 3306,
    MysqlDSN::DBNAME      => 'my_database',
    MysqlDSN::CHARSET     => 'utf8mb4',
    MysqlDSN::UNIX_SOCKET => '/tmp/mysql.sock',
]);

echo (string) $dsn;
// mysql:host=127.0.0.1;port=3306;dbname=my_database;charset=utf8mb4;unix_socket=/tmp/mysql.sock
```

### Build a PDO connection

```php
use oihana\mysql\MysqlPDOBuilder;

$pdo = (new MysqlPDOBuilder([
    'host'     => 'localhost',
    'dbname'   => 'test_db',
    'username' => 'user',
    'password' => 'secret',
    // 'validate'   => false, // disable validation if needed
    // 'skipDbName' => true,  // build DSN without dbname
]))();
```

### Administrative operations

```php
use oihana\mysql\MysqlModel;

$model = new MysqlModel();
$model->setPDO( $pdoAdmin ); // connect as root/admin

$model->createDatabase('my_app');
$model->createUser('myuser', 'localhost', 'securepass');
$model->grantPrivileges('myuser', 'localhost', 'my_app');
$model->flushPrivileges();

// Rename a user
$model->renameUser('myuser', 'localhost', 'user', 'localhost');

// Revoke privileges
$model->revokePrivileges('user', 'localhost', 'my_app');

// Export information
print_r( $model->toArray() );
```

## ✅ Running Unit Tests

To run all tests:
```bash
composer run-script test
```

To run a specific test file:
```bash
composer run test ./tests/oihana/mysql/MysqlDSNTest.php
```

## 🤝 Contributing

Contributions are welcome! Please:

- Open an issue for discussion before large changes
- Write tests for new features and bug fixes
- Run the full test suite locally before submitting a PR

## 🗒️ Changelog

See `CHANGELOG.md` for notable changes.

## 🧾 License

This project is licensed under the [Mozilla Public License 2.0 (MPL-2.0)](https://www.mozilla.org/en-US/MPL/2.0/).

## 👤 About the author

- Author: Marc ALCARAZ (aka eKameleon)
- Mail: marc@ooop.fr
- Website: http://www.ooop.fr

## 🛠️ Generate the Documentation

We use [phpDocumentor](https://phpdoc.org/) to generate the documentation into the `./docs` folder.

```bash
composer doc
```

## 🔗 Related packages

- `oihana/php-system` – standard set of PHP helpers and tools (PDO model, logging, init, ...): `https://github.com/BcommeBois/oihana-php-system`
- `oihana/php-core` – core helpers and utilities used by this library: `https://github.com/BcommeBois/oihana-php-core`
- `oihana/php-enums` – a collection of strongly-typed constant enumerations for PHP: `https://github.com/BcommeBois/oihana-php-enums`
- `oihana/php-reflect` – reflection and hydration utilities: `https://github.com/BcommeBois/oihana-php-reflect`
