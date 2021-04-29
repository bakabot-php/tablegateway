<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Integration;

class Sqlite3TestTableTest extends SqliteTestTableTest
{
    protected static function getDbalConnectionUrl(): string
    {
        return 'sqlite3://' . static::$sqliteFile;
    }
}
