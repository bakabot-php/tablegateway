<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Integration;

class MysqliTestTableTest extends AbstractTestTableTest
{
    protected static function getDbalConnectionUrl(): string
    {
        return 'mysqli://root:test@mysql:3306/tablegateway_test';
    }
}
