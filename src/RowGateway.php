<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway;

use LogicException;

class RowGateway
{
    private int $id = 0;
    /** @var array<string, mixed> */
    private array $rowGatewayData = [];

    final public function getId(): int
    {
        if ($this->id === 0) {
            throw new LogicException('This row has expired.');
        }

        return $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    final public function toArray(): array
    {
        return $this->rowGatewayData;
    }
}
