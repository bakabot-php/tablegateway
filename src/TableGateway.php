<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway;

use Bakabot\TableGateway\Exception\InitializationException;
use Doctrine\DBAL\Connection;
use ReflectionClass;
use Throwable;

/**
 * @template T of RowGateway
 */
abstract class TableGateway extends AbstractTableGateway
{
    private ?ReflectionClass $reflection = null;

    /**
     * @param T $rowGatewayPrototype
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

        parent::__construct(
            $this->tableName,
            $connection,
            $rowGatewayPrototype ?? new RowGateway(),
            $rowGatewayHydrator ?? RowGatewayHydrator::factory($connection, $this->getColumnTypes())
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
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->getReflectionClass()->getShortName()));
        $tableName = str_replace('_table', '', $tableName);

        return $tableName;
    }

    protected function determineTableName(): string
    {
        return $this->inferTableName();
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
            $this->connection->transactional(function () use ($seedData) {
                foreach ($seedData as $row) {
                    $this->create($row);
                }
            });
        } catch (Throwable $ex) {
            throw InitializationException::seedingError($this, $ex);
        }
    }
}
