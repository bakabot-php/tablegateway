<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Integration;

class MariaDbPdoTestTableTest extends AbstractTestTableTest
{
    protected static function getDbalConnectionUrl(): string
    {
        return 'mysql://root:test@mariadb:3306/tablegateway_test';
    }
}
