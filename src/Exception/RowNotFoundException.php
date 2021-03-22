<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Exception;

use Bakabot\TableGateway\AbstractTableGateway;
use RuntimeException;
use Throwable;

final class RowNotFoundException extends RuntimeException
{
    private function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function lookupError(AbstractTableGateway $tableGateway, int $id): self
    {
        return new self(
            sprintf(
                "Table \"%s\" doesn't have a row with ID #%d.",
                (string) $tableGateway,
                $id
            )
        );
    }
}
