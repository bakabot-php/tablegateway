<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Unit;

use Bakabot\TableGateway\RowGateway;
use Bakabot\TableGateway\RowGatewayHydrator;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class RowGatewayHydratorTest extends TestCase
{
    private function createHydrator(array $columns = []): RowGatewayHydrator
    {
        return RowGatewayHydrator::factory($this->createMock(Connection::class), $columns);
    }

    /** @test */
    public function can_hydrate_simple_fields(): void
    {
        $rowPrototype = new class extends RowGateway {
            private string $name;

            public function getName(): string
            {
                return $this->name;
            }
        };

        $hydrator = $this->createHydrator(['name' => 'string']);
        $hydrator->hydrate($rowPrototype, ['id' => 1, 'name' => 'Simple Test'], false);

        self::assertSame('Simple Test', $rowPrototype->getName());
    }

    /** @test */
    public function can_hydrate_snake_cased_fields(): void
    {
        $rowPrototype = new class extends RowGateway {
            private bool $is_winner;

            public function isWinner(): bool
            {
                return $this->is_winner;
            }
        };

        $hydrator = $this->createHydrator(['is_winner' => 'bool']);
        $hydrator->hydrate($rowPrototype, ['id' => 1, 'is_winner' => true], false);

        self::assertTrue($rowPrototype->isWinner());
    }

    /** @test */
    public function can_hydrate_camel_cased_fields(): void
    {
        $rowPrototype = new class extends RowGateway {
            private bool $isWinner;

            public function isWinner(): bool
            {
                return $this->isWinner;
            }
        };

        $hydrator = $this->createHydrator(['is_winner' => 'bool']);
        $hydrator->hydrate($rowPrototype, ['id' => 1, 'is_winner' => true], false);

        self::assertTrue($rowPrototype->isWinner());
    }
}
