<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Integration;

class SqliteTestTableTest extends AbstractTestTableTest
{
    protected static string $sqliteFile;

    public static function setUpBeforeClass(): void
    {
        self::$sqliteFile = uniqid('tablegateway_test_', true) . '.sqlite';

        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        @unlink(static::$sqliteFile);
    }

    protected static function getDbalConnectionUrl(): string
    {
        return 'sqlite://' . static::$sqliteFile;
    }
}
