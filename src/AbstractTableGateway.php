<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway;

use Bakabot\TableGateway\Exception\InitializationException;
use Bakabot\TableGateway\Exception\RowNotFoundException;
use Countable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Table;
use Generator;
use Stringable;
use Throwable;

/**
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
     * @param T $rowGatewayPrototype
     * @param Connection $connection
     * @param RowGatewayHydrator $rowGatewayHydrator
     */
    public function __construct(
        string $tableName,
        RowGateway $rowGatewayPrototype,
        Connection $connection,
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
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist((array) $this->tableName)) {
            return;
        }

        try {
            $schemaManager->createTable($this->getTableDefinition());
        } catch (Throwable $ex) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
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
        /** @var array<string, mixed> $mergedDefaults */
        $mergedDefaults = array_replace($this->getDefaultValues(), $data, ['id' => $id]);

        return $mergedDefaults;
    }

    /**
     * @return array<string, string>
     */
    final protected function getColumnTypes(): array
    {
        if ($this->columnTypes === null) {
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
        $result = $this
            ->getQueryBuilder()
            ->select('*')
            ->orderBy('id', 'asc')
            ->executeQuery()
        ;

        foreach ($result->iterateAssociative() as $data) {
            yield $this->cloneRowGateway($data);
        }
    }

    final public function count(): int
    {
        $result = $this
            ->getQueryBuilder()
            ->select('COUNT(*)')
            ->executeQuery()
        ;

        return (int) $result->fetchFirstColumn()[0];
    }

    /**
     * @param array<string, mixed> $data
     * @return T
     */
    final public function create(array $data): RowGateway
    {
        $id = $this->connection->transactional(
            function (Connection $conn) use ($data): int {
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

        /** @var bool $success */
        $success = $this->connection->transactional(
            function (Connection $conn) use ($id): bool {
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
        $result = $this
            ->getQueryBuilder()
            ->select('*')
            ->where('id = ?')
            ->setParameter(0, $id, 'integer')
            ->executeQuery()
        ;

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

        /** @var bool $success */
        $success = $this->connection->transactional(
            function (Connection $conn) use ($id, $updatedFields): bool {
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
            function (QueryBuilder $qb) use ($column, $value): CompositeExpression {
                return $qb->expr()->and(
                    $qb->expr()->eq($column, $qb->createNamedParameter($value))
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
        $qb = $this->getQueryBuilder();

        $result = $qb
            ->select('*')
            ->where($expression($qb))
            ->executeQuery()
        ;

        foreach ($result->iterateAssociative() as $data) {
            yield $this->cloneRowGateway($data);
        }
    }

    public function __toString(): string
    {
        return $this->tableName;
    }
}
