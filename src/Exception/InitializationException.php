<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Exception;

use Bakabot\TableGateway\AbstractTableGateway;
use RuntimeException;
use Throwable;

final class InitializationException extends RuntimeException
{
    private function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function seedingError(AbstractTableGateway $tableGateway, Throwable $previous): self
    {
        return new self(
            sprintf(
                'Seeding of table "%s" failed during initialization of [%s].',
                (string) $tableGateway,
                $tableGateway::class
            ),
            $previous
        );
    }

    public static function tableCreationError(AbstractTableGateway $tableGateway, Throwable $previous): self
    {
        return new self(
            sprintf(
                'Creation of table "%s" failed during initialization of [%s].',
                (string) $tableGateway,
                $tableGateway::class
            ),
            $previous
        );
    }
}
