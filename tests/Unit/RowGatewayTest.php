<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Unit;

use Bakabot\TableGateway\RowGateway;
use Bakabot\TableGateway\RowGatewayHydrator;
use Doctrine\DBAL\Connection;
use LogicException;
use PHPUnit\Framework\TestCase;

class RowGatewayTest extends TestCase
{
    private RowGatewayHydrator $hydrator;

    /**
     * @template T of RowGatewayInterface
     *
     * @param array $data
     * @param class-string<T> $rowGatewayClass
     * @return T
     */
    private function createHydratedRow(
        array $data,
        string $rowGatewayClass = RowGateway::class
    ): RowGateway {
        if (!isset($this->hydrator)) {
            $this->hydrator = RowGatewayHydrator::factory($this->createMock(Connection::class), []);
        }

        $row = new $rowGatewayClass();

        $this->hydrator->hydrate($row, $data, false);

        return $row;
    }

    /** @test */
    public function empty_row_is_always_considered_expired(): void
    {
        $this->expectException(LogicException::class);

        $row = new RowGateway();
        $row->getId();
    }

    /** @test */
    public function returns_provided_data(): void
    {
        $data = ['id' => 1];
        $row = $this->createHydratedRow($data);

        self::assertSame($data, $row->toArray());
    }

    /** @test */
    public function returns_provided_id(): void
    {
        $rowId = 1;
        $row = $this->createHydratedRow(['id' => $rowId]);

        self::assertSame($rowId, $row->getId());
    }
}
