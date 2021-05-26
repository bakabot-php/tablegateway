<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway;

use Bakabot\TableGateway\Exception\InitializationException;
use Doctrine\DBAL\Connection;
use ReflectionClass;
use Throwable;

/**
 * @template T of RowGateway
 * @extends AbstractTableGateway<T>
 */
abstract class TableGateway extends AbstractTableGateway
{
    private ?ReflectionClass $reflection = null;

    /** @var string */
    protected const DELIMITER = '_';

    /**
     * @param T|null $rowGatewayPrototype
     * @param Connection|null $connection
     * @param RowGatewayHydrator|null $rowGatewayHydrator
     */
    public function __construct(
        ?RowGateway $rowGatewayPrototype = null,
        ?Connection $connection = null,
        ?RowGatewayHydrator $rowGatewayHydrator = null
    ) {
        $this->tableName = $this->determineTableName();

        $connection = $connection ?? GlobalConnection::get();
        $rowGatewayHydrator = $rowGatewayHydrator ?? RowGatewayHydrator::create($connection, $this->getColumnTypes());

        /** @var T $rowGatewayPrototype */
        $rowGatewayPrototype = $rowGatewayPrototype ?? $this->getRowGatewayPrototype();

        parent::__construct(
            $this->tableName,
            $rowGatewayPrototype,
            $connection,
            $rowGatewayHydrator
        );
    }

    private function getReflectionClass(): ReflectionClass
    {
        if ($this->reflection === null) {
            $this->reflection = new ReflectionClass(static::class);
        }

        return $this->reflection;
    }

    private function inferTableName(): string
    {
        $inferredTableName = strtolower(
            preg_replace(
                '/(?<!^)[A-Z]/',
                sprintf('%s$0', self::DELIMITER),
                $this->getReflectionClass()->getShortName()
            )
        );

        $tableSuffix = sprintf('%s%s', self::DELIMITER, 'table');

        return str_replace($tableSuffix, '', $inferredTableName);
    }

    protected function determineTableName(): string
    {
        return $this->inferTableName();
    }

    /**
     * @return T
     */
    protected function getRowGatewayPrototype(): RowGateway
    {
        /** @var T $rowGatewayPrototype */
        $rowGatewayPrototype = new RowGateway();

        return $rowGatewayPrototype;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getSeedData(): array
    {
        return [];
    }

    protected function postInitialize(): void
    {
        $seedData = $this->getSeedData();

        if ($seedData === []) {
            return;
        }

        try {
            $this->connection->transactional(function () use ($seedData): void {
                foreach ($seedData as $row) {
                    $this->create($row);
                }
            });
        } catch (Throwable $ex) {
            throw InitializationException::seedingError($this, $ex);
        }
    }
}
