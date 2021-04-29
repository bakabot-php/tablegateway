<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Integration;

class MariadbMysqliTestTableTest extends AbstractTestTableTest
{
    protected static function getDbalConnectionUrl(): string
    {
        return 'mysqli://root:test@mariadb:3306/tablegateway_test';
    }
}
