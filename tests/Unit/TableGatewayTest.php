<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Unit;

use Bakabot\TableGateway\Exception\InitializationException;
use Bakabot\TableGateway\Integration\Table\BeatlesMembersTable;
use Bakabot\TableGateway\Integration\Table\DummyTable;
use Bakabot\TableGateway\TableGateway;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TableGatewayTest extends TestCase
{
    private function getTableGatewayMock(string $className): TableGateway
    {
        $schemaManager = $this
            ->getMockBuilder(AbstractSchemaManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['tablesExist'])
            ->getMockForAbstractClass();

        $schemaManager->method('tablesExist')->willReturn(true);

        $connection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createSchemaManager'])
            ->getMock();

        $connection->method('createSchemaManager')->willReturn($schemaManager);

        return $this->getMockForAbstractClass(
            TableGateway::class,
            [
                null,
                $connection,
                null
            ],
            $className,
        );
    }

    /** @test */
    public function infers_table_name_from_class_name(): void
    {
        $table = $this->getTableGatewayMock('AwesomeData');

        self::assertSame('awesome_data', (string) $table);
    }

    /** @test */
    public function inferring_strips_table_suffix(): void
    {
        $table = $this->getTableGatewayMock('AwesomeDataTable');

        self::assertSame('awesome_data', (string) $table);
    }

    /**
     * @test
     * @depends inferring_strips_table_suffix
     */
    public function table_creation_failure_throws_custom_exception(): void
    {
        $className = DummyTable::class;
        $tableName = 'dummy';

        $this->expectException(InitializationException::class);
        $this->expectExceptionMessage(
            sprintf('Creation of table "%s" failed during initialization of [%s].', $tableName, $className)
        );

        $schemaManager = $this
            ->getMockBuilder(AbstractSchemaManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createTable', 'tablesExist'])
            ->getMockForAbstractClass();

        $schemaManager->method('createTable')->willThrowException(new RuntimeException('Table could not be created.'));
        $schemaManager->method('tablesExist')->willReturn(false);

        $connection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createSchemaManager'])
            ->getMock();

        $connection->method('createSchemaManager')->willReturn($schemaManager);

        new $className(null, $connection);
    }

    /**
     * @test
     * @depends table_creation_failure_throws_custom_exception
     */
    public function initialization_returns_early_without_seed_data(): void
    {
        $schemaManager = $this
            ->getMockBuilder(AbstractSchemaManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createTable', 'tablesExist'])
            ->getMockForAbstractClass();

        $schemaManager->method('createTable')->willReturn(null);
        $schemaManager->method('tablesExist')->willReturn(false);

        $connection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createSchemaManager', 'transactional'])
            ->getMock();

        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->expects(self::never())->method('transactional');

        new DummyTable(null, $connection);
    }

    /**
     * @test
     * @depends infers_table_name_from_class_name
     */
    public function seeding_failure_throws_custom_exception(): void
    {
        $this->expectException(InitializationException::class);
        $this->expectExceptionMessage(
            sprintf('Seeding of table "%s" failed during initialization of [%s].', 'beatles_members', BeatlesMembersTable::class)
        );

        $schemaManager = $this
            ->getMockBuilder(AbstractSchemaManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createTable', 'tablesExist'])
            ->getMockForAbstractClass();

        $schemaManager->method('createTable')->willReturn(null);
        $schemaManager->method('tablesExist')->willReturn(false);

        $connection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createSchemaManager', 'transactional'])
            ->getMock();

        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->method('transactional')->willThrowException(new RuntimeException('Insert failed.'));

        new BeatlesMembersTable(null, $connection);
    }
}
