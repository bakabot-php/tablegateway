<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway;

use Bakabot\TableGateway\Exception\NoGlobalConnectionException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PDO;

final class GlobalConnection
{
    /** @var array<string, Connection> */
    private static array $instances = [];

    /** @var string */
    private const DEFAULT = 'default';

    public static function fromPdo(PDO $pdo, string $name = self::DEFAULT): void
    {
        $driverName = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        assert(is_string($driverName));

        self::$instances[$name] = DriverManager::getConnection(
            [
                'driver' => sprintf('pdo_%s', $driverName),
                'pdo' => $pdo,
            ]
        );
    }

    public static function get(string $name = self::DEFAULT): Connection
    {
        if (!isset(self::$instances[$name])) {
            throw new NoGlobalConnectionException();
        }

        return self::$instances[$name];
    }

    public static function set(Connection $instance, string $name = self::DEFAULT): void
    {
        self::$instances[$name] = $instance;
    }
}
