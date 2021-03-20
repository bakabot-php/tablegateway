<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Integration;

class PostgresTestTableTest extends AbstractTestTableTest
{
    protected static function getDbalConnectionUrl(): string
    {
        return 'postgres://root:test@postgres:5432/tablegateway_test';
    }
}
