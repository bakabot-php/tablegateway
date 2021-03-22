<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway;

use Bakabot\TableGateway\Exception\InitializationException;
use Doctrine\DBAL\Connection;
use Throwable;

/**
 * @template T of RowGateway
 */
abstract class TableGateway extends AbstractTableGateway
{
    use ClassBasedTableNameTrait;

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
        $this->tableName = $this->inferTableName();

        $connection = $connection ?? GlobalConnection::get();

        parent::__construct(
            $this->tableName,
            $connection,
            $rowGatewayPrototype ?? new RowGateway(),
            $rowGatewayHydrator ?? RowGatewayHydrator::factory($connection, $this->getColumnTypes())
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getSeedData(): array
    {
        return [];
    }

    final protected function postInitialize(): void
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
