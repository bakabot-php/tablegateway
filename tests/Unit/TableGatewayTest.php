<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Unit;

use Bakabot\TableGateway\TableGateway;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\TestCase;

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
            ->onlyMethods(['getSchemaManager'])
            ->getMock();

        $connection->method('getSchemaManager')->willReturn($schemaManager);

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
}
