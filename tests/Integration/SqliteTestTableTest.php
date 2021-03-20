<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Integration;

class SqliteTestTableTest extends AbstractTestTableTest
{
    protected static string $sqliteFile;

    public static function setUpBeforeClass(): void
    {
        self::$sqliteFile = tempnam('/tmp', 'tablegateway_test_');

        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        @unlink(self::$sqliteFile);
    }

    protected static function getDbalConnectionUrl(): string
    {
        return 'sqlite://' . self::$sqliteFile;
    }
}
