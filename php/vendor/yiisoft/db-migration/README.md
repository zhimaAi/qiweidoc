<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii DB Migration</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-migration/v)](https://packagist.org/packages/yiisoft/db-migration)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-migration/downloads)](https://packagist.org/packages/yiisoft/db-migration)
[![Build status](https://github.com/yiisoft/db-migration/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/db-migration/actions/workflows/build.yml)
[![codecov](https://codecov.io/gh/yiisoft/db-migration/graph/badge.svg?token=CCRKELEOHP)](https://codecov.io/gh/yiisoft/db-migration)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-migration%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-migration/master)
[![static analysis](https://github.com/yiisoft/db-migration/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/db-migration/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/db-migration/coverage.svg)](https://shepherd.dev/github/yiisoft/db-migration)

Yii DB Migration allows you to manage database schema using migrations.

Supports the following databases out of the box:

| DBMS | Version |
|----|----------|
| [MSSQL](https://www.microsoft.com/en-us/sql-server/sql-server-2019) | **2017, 2019, 2022** |
| [MySQL](https://www.mysql.com/) | **5.7–8.0** |
| [MariaDB](https://mariadb.org/) | **10.4–10.9** |
| [Oracle](https://www.oracle.com/database/) | **12c–21c** |
| [PostgreSQL](https://www.postgresql.org/) |  **9.6–15** |
| [SQLite](https://www.sqlite.org) | **3.3 and above** |

## Requirements

- PHP 8.0 or higher.
- `Filter` PHP extension.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/db-migration
```

## Command list

```text
migrate:create   Creates a new migration.
migrate:down     Reverts the specified number of latest migrations.
migrate:history  Displays the migration history.
migrate:new      Displays not yet applied migrations.
migrate:redo     Redoes the last few migrations.
migrate:up       Applies new migrations.
```

The create command allows defining fields for the table being created.

## Documentation

- Guide: [English](docs/guide/en/README.md), [Português - Brasil](docs/guide/pt-BR/README.md)
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii DB Migration is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
