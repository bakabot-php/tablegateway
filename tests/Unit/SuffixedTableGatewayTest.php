<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Unit;

use Bakabot\TableGateway\SuffixedTableGateway;
use Bakabot\TableGateway\TableGateway;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SuffixedTableGatewayTest extends TestCase
{
    private function getTableGatewayMock(string $className, string $suffix): TableGateway
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
            SuffixedTableGateway::class,
            [
                $suffix,
                null,
                $connection,
                null
            ],
            $className,
        );
    }

    /** @test */
    public function empty_prefix_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getTableGatewayMock('ServerSettings', '');
    }

    /** @test */
    public function invalid_prefix_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getTableGatewayMock('ServerSettings', '-');
    }

    /** @test */
    public function infers_table_name_from_class_name(): void
    {
        $suffix = 'discord_1234';
        $table = $this->getTableGatewayMock('ServerSettings', $suffix);

        self::assertSame('server_settings_discord_1234', (string) $table);
        self::assertSame($suffix, $table->getSuffix());
    }

    /** @test */
    public function inferring_strips_table_suffix(): void
    {
        $suffix = 'twitch_1234';
        $table = $this->getTableGatewayMock('ServerSettingsTable', $suffix);

        self::assertSame('server_settings_twitch_1234', (string) $table);
        self::assertSame($suffix, $table->getSuffix());
    }

    /** @test */
    public function normalizes_suffix(): void
    {
        $suffix = 'twitch-1234';
        $table = $this->getTableGatewayMock('ServerSettingsTable', $suffix);

        self::assertSame('server_settings_twitch_1234', (string) $table);
    }
}
