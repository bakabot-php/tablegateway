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

    /** @var string */
    protected const DELIMITER = '_';

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
            $rowGatewayPrototype ?? $this->getRowGatewayPrototype(),
            $connection,
            $rowGatewayHydrator ?? RowGatewayHydrator::create($connection, $this->getColumnTypes())
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

    protected function getRowGatewayPrototype(): RowGateway
    {
        return new RowGateway();
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
