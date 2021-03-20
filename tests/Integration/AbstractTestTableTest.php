<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Integration;

use Bakabot\TableGateway\Integration\Table\BeatlesMembersTable;
use Bakabot\TableGateway\RowGateway;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Throwable;

abstract class AbstractTestTableTest extends TestCase
{
    private static Connection $connection;

    private const UNKNOWN_ID = 9001;

    public static function dropTable(): void
    {
        $schemaManager = static::getDbalConnection()->getSchemaManager();

        $tableName = (string) static::getTestTable();

        try {
            $schemaManager->dropTable($tableName);
        } catch (Throwable) {

        }
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::dropTable();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        static::dropTable();
    }

    protected static function getDbalConnection(): Connection
    {
        if (!isset(self::$connection)) {
            self::$connection = DriverManager::getConnection(
                [
                    'url' => static::getDbalConnectionUrl(),
                ]
            );
        }

        return self::$connection;
    }

    abstract protected static function getDbalConnectionUrl(): string;

    protected static function getTestTable(): BeatlesMembersTable
    {
        return new BeatlesMembersTable(null, static::getDbalConnection());
    }

    /** @test */
    public function creates_table_automagically(): void
    {
        $table = (string) static::getTestTable();

        $schemaManager = static::getDbalConnection()->getSchemaManager();

        self::assertTrue($schemaManager->tablesExist((array) $table));
    }

    /**
     * @test
     * @depends creates_table_automagically
     *
     * @see BeatlesMembersTable::getSeedData()
     */
    public function can_fetch_pre_seeded_data(): void
    {
        $table = static::getTestTable();

        self::assertSame(4, $table->count());

        $returnedNames = [];
        foreach ($table->all() as $beatlesMember) {
            $returnedNames[] = $beatlesMember->toArray()['name'];
        }

        self::assertEquals(
            [
                'John Lennon',
                'Paul McCartney',
                'George Harrison',
                'Ringo Starr',
            ],
            $returnedNames
        );
    }

    /**
     * @test
     * @depends creates_table_automagically
     *
     * @see BeatlesMembersTable::getSeedData()
     */
    public function can_insert_new_row(): RowGateway
    {
        $table = static::getTestTable();
        $row = $table->create(['name' => 'Hugo Degenhardt', 'is_best_beatle' => false]);

        self::assertInstanceOf(RowGateway::class, $row);

        $data = $row->toArray();

        self::assertSame('Hugo Degenhardt', $data['name']);
        self::assertFalse($data['is_best_beatle']);

        return $row;
    }

    /**
     * @test
     * @depends creates_table_automagically
     *
     * @see BeatlesMembersTable::getTableDefinition()
     */
    public function can_insert_new_row_with_defaults_applied(): RowGateway
    {
        $table = static::getTestTable();
        $row = $table->create(['name' => 'Rick Rock']);

        self::assertInstanceOf(RowGateway::class, $row);

        $data = $row->toArray();

        self::assertSame('Rick Rock', $data['name']);
        self::assertFalse($data['is_best_beatle']);

        return $row;
    }

    /**
     * @test
     * @depends can_insert_new_row
     */
    public function can_lookup_row_by_id(RowGateway $row): int
    {
        $id = $row->getId();
        $table = static::getTestTable();
        $rowCopy = $table->get($id);

        self::assertEquals($row->toArray(), $rowCopy->toArray());
        self::assertSame($id, $rowCopy->getId());

        return $id;
    }

    /**
     * @test
     * @depends can_lookup_row_by_id
     */
    public function cannot_lookup_row_by_unknown_id(): void
    {
        self::assertNull(static::getTestTable()->get(self::UNKNOWN_ID));
    }

    /**
     * @test
     * @depends can_insert_new_row
     */
    public function can_lookup_row_by_where_condition(RowGateway $row): void
    {
        $id = $row->getId();
        $table = static::getTestTable();
        $rowCopy = iterator_to_array($table->where('name', 'Hugo Degenhardt'))[0];

        self::assertEquals($row->toArray(), $rowCopy->toArray());
        self::assertSame($id, $rowCopy->getId());
    }

    /**
     * @test
     * @depends can_lookup_row_by_id
     */
    public function can_update_row_by_id(int $id): void
    {
        $table = static::getTestTable();

        self::assertTrue($table->update($id, ['name' => 'otto']));
    }

    /**
     * @test
     * @depends can_update_row_by_id
     */
    public function cannot_update_row_by_unknown_id(): void
    {
        self::assertFalse(static::getTestTable()->update(self::UNKNOWN_ID, []));
    }

    /**
     * @test
     * @depends can_lookup_row_by_id
     */
    public function can_delete_row_by_id(int $id): void
    {
        $table = static::getTestTable();

        self::assertTrue($table->delete($id));
    }

    /**
     * @test
     * @depends can_delete_row_by_id
     */
    public function cannot_delete_row_by_unknown_id(): void
    {
        self::assertFalse(static::getTestTable()->delete(self::UNKNOWN_ID));
    }

    /** @test */
    public function can_clear_table(): void
    {
        $table = static::getTestTable();
        $table->clear();

        self::assertSame(0, $table->count());
    }
}