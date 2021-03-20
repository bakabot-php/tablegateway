<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use LogicException;
use PDO;

final class GlobalConnection
{
    private static ?Connection $instance = null;

    public static function fromPdo(PDO $pdo): void
    {
        $driverName = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        assert(is_string($driverName));

        self::$instance = DriverManager::getConnection(
            [
                'driver' => sprintf('pdo_%s', $driverName),
                'pdo' => $pdo,
            ]
        );
    }

    public static function get(): Connection
    {
        if (self::$instance === null) {
            throw new LogicException();
        }

        return self::$instance;
    }

    public static function set(Connection $instance): void
    {
        self::$instance = $instance;
    }
}
