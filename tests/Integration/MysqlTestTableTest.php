<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Integration;

class MysqlTestTableTest extends AbstractTestTableTest
{
    protected static function getDbalConnectionUrl(): string
    {
        return 'mysql://root:test@mysql:3306/tablegateway_test';
    }
}
