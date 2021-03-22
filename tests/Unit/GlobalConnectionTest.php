<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Unit;

use Bakabot\TableGateway\Exception\NoGlobalConnectionException;
use Bakabot\TableGateway\GlobalConnection;
use Doctrine\DBAL\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * @backupGlobals
 * @backupStaticAttributes
 */
class GlobalConnectionTest extends TestCase
{
    /** @test */
    public function errors_when_no_global_connection_set(): void
    {
        $this->expectExceptionObject(new NoGlobalConnectionException());

        GlobalConnection::get();
    }

    /** @test */
    public function can_create_dbal_connection_from_pdo(): void
    {
        $driver = 'mysql';

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock
            ->expects(self::once())
            ->method('getAttribute')
            ->with(PDO::ATTR_DRIVER_NAME)
            ->willReturn($driver);

        GlobalConnection::fromPdo($pdoMock);

        self::assertSame($driver, GlobalConnection::get()->getDriver()->getDatabasePlatform()->getName());
    }

    /** @test */
    public function returns_previously_set_connection(): void
    {
        $conn = $this->createMock(Connection::class);

        GlobalConnection::set($conn);

        self::assertSame($conn, GlobalConnection::get());
    }
}
