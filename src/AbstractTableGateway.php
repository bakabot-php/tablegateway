<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway;

use Bakabot\TableGateway\Exception\InitializationException;
use Bakabot\TableGateway\Exception\RowNotFoundException;
use Countable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Generator;
use Stringable;
use Throwable;

/**
 * @internal
 * @psalm-internal Bakabot\TableGateway
 *
 * @template T of RowGateway
 */
abstract class AbstractTableGateway implements Countable, Stringable
{
    /** @var array<string, string> */
    private ?array $columnTypes = null;
    private ?QueryBuilder $queryBuilder = null;
    private RowGatewayHydrator $rowGatewayHydrator;
    /** @var T */
    private RowGateway $rowGatewayPrototype;
    protected Connection $connection;
    protected string $tableName;

    /**
     * @param string $tableName
     * @param Connection $connection
     * @param T $rowGatewayPrototype
     * @param RowGatewayHydrator $rowGatewayHydrator
     */
    public function __construct(
        string $tableName,
        Connection $connection,
        RowGateway $rowGatewayPrototype,
        RowGatewayHydrator $rowGatewayHydrator
    ) {
        $this->connection = $connection;
        $this->rowGatewayHydrator = $rowGatewayHydrator;
        $this->rowGatewayPrototype = $rowGatewayPrototype;
        $this->tableName = $tableName;

        $this->initialize();
    }

    /**
     * @param array<string, mixed> $data
     * @return T
     */
    private function cloneRowGateway(array $data): RowGateway
    {
        $copy = clone $this->rowGatewayPrototype;
        $this->rowGatewayHydrator->hydrate($copy, $data);

        return $copy;
    }

    private function identify(int|RowGateway $identity): int
    {
        if (is_int($identity)) {
            return $identity;
        }

        return $identity->getId();
    }

    private function initialize(): void
    {
        /** @var AbstractSchemaManager $schemaManager */
        $schemaManager = $this->connection->getSchemaManager();

        if ($schemaManager->tablesExist((array) $this->tableName)) {
            return;
        }

        try {
            $schemaManager->createTable($this->getTableDefinition());
        } catch (Throwable $ex) {
            throw InitializationException::tableCreationError($this, $ex);
        }

        $this->postInitialize();
    }

    /**
     * @param int $id
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function mergeDefaultValues(int $id, array $data): array
    {
        return array_replace($this->getDefaultValues(), $data, ['id' => $id]);
    }

    /**
     * @return array<string, string>
     */
    final protected function getColumnTypes(): array
    {
        if (!isset($this->columnTypes)) {
            $this->columnTypes = [];

            foreach ($this->getTableDefinition()->getColumns() as $column) {
                $this->columnTypes[$column->getName()] = $column->getType()->getName();
            }
        }

        return $this->columnTypes;
    }

    protected function getDefaultValues(): array
    {
        $defaults = [];
        foreach ($this->getTableDefinition()->getColumns() as $column) {
            $defaults[$column->getName()] = $column->getDefault();
        }

        return $defaults;
    }

    final protected function getQueryBuilder(): QueryBuilder
    {
        if (!isset($this->queryBuilder)) {
            $this->queryBuilder = $this->connection
                ->createQueryBuilder()
                ->from($this->tableName);
        }

        return clone $this->queryBuilder;
    }

    protected function getTableDefinition(): Table
    {
        $table = new Table($this->tableName);
        $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
        $table->setPrimaryKey(['id'], 'pk_id');

        return $table;
    }

    /** @codeCoverageIgnore */
    protected function postInitialize(): void
    {
    }

    /**
     * @return Generator<T>
     */
    final public function all(): Generator
    {
        $query = $this
            ->getQueryBuilder()
            ->select('*')
            ->orderBy('id', 'asc');

        /** @var Result $result */
        $result = $query->execute();

        foreach ($result->iterateAssociative() as $data) {
            yield $this->cloneRowGateway($data);
        }
    }

    final public function count(): int
    {
        $qb = $this
            ->getQueryBuilder()
            ->select('COUNT(*)');

        /** @var Result $result */
        $result = $qb->execute();

        return (int) $result->fetchFirstColumn()[0];
    }

    /**
     * @param array<string, mixed> $data
     * @return T
     */
    final public function create(array $data): RowGateway
    {
        $id = $this->connection->transactional(
            function (Connection $conn) use ($data) {
                unset($data['id']);

                $conn->insert($this->tableName, $data, $this->getColumnTypes());

                return (int) $conn->lastInsertId();
            }
        );

        assert(is_int($id));

        return $this->cloneRowGateway($this->mergeDefaultValues($id, $data));
    }

    final public function delete(int|RowGateway $identity): bool
    {
        $id = $this->identify($identity);

        $success = $this->connection->transactional(
            function (Connection $conn) use ($id) {
                return $conn->delete($this->tableName, ['id' => $id], $this->getColumnTypes()) > 0;
            }
        );

        if ($success && $identity instanceof RowGateway) {
            $this->rowGatewayHydrator->hydrate($identity, ['id' => 0]);
        }

        return $success;
    }

    /**
     * @param int $id
     * @return T
     */
    final public function find(int $id): RowGateway
    {
        $query = $this->getQueryBuilder();
        $query = $query
            ->select('*')
            ->where('id = ?')
            ->setParameter(0, $id, 'integer');

        /** @var Result $result */
        $result = $query->execute();
        $data = $result->fetchAssociative();

        if (!$data) {
            throw RowNotFoundException::lookupError($this, $id);
        }

        return $this->cloneRowGateway($data);
    }

    /**
     * @param int|RowGateway $identity
     * @param array<string, mixed> $updatedFields
     * @return bool
     */
    final public function update(int|RowGateway $identity, array $updatedFields): bool
    {
        unset($updatedFields['id']);

        if ($updatedFields === []) {
            return false;
        }

        $id = $this->identify($identity);

        $success = $this->connection->transactional(
            function (Connection $conn) use ($id, $updatedFields) {
                return $conn->update($this->tableName, $updatedFields, ['id' => $id], $this->getColumnTypes()) > 0;
            }
        );

        if ($success && $identity instanceof RowGateway) {
            $this->rowGatewayHydrator->hydrate($identity, array_replace(['id' => $id], $updatedFields));
        }

        return $success;
    }

    /**
     * @param string $column
     * @param mixed $value
     * @return Generator<T>
     */
    final public function where(string $column, mixed $value): Generator
    {
        return $this->whereExpression(
            function (QueryBuilder $qb) use ($column, $value) {
                return $qb->expr()->and(
                    $qb->expr()->eq($column, $this->connection->quote($value))
                );
            }
        );
    }

    /**
     * @param callable $expression
     * @return Generator<T>
     */
    final public function whereExpression(callable $expression): Generator
    {
        $query = $this->getQueryBuilder();
        $query = $query
            ->select('*')
            ->where($expression($query));

        /** @var Result $result */
        $result = $query->execute();

        foreach ($result->iterateAssociative() as $data) {
            yield $this->cloneRowGateway($data);
        }
    }

    public function __toString(): string
    {
        return $this->tableName;
    }
}
