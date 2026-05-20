# Oihana PHP Mysql OpenSource library - Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added

- Initial extraction of the `oihana\mysql` namespace from [`oihana/php-system`](https://github.com/BcommeBois/oihana-php-system).
- Classes
  - `MysqlDSN` – builds a MySQL DSN string from a structured configuration.
  - `MysqlPDOBuilder` – creates a configured `PDO` instance with sane defaults and optional validation.
  - `MysqlModel` – high-level administrative model for databases, users, privileges and tables.
- Traits
  - `MysqlAssertionsTrait`
  - `MysqlDatabaseTrait`
  - `MysqlPrivilegeTrait`
  - `MysqlRootTrait`
  - `MysqlTableTrait`
  - `MysqlUserTrait`
- Enums
  - `MysqlParam` (with `MysqlParamTrait`)
  - `MysqlPrivileges`
