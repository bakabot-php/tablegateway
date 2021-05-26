<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway;

use Doctrine\DBAL\Connection;
use InvalidArgumentException;

/**
 * @template T of RowGateway
 * @extends TableGateway<T>
 */
abstract class SuffixedTableGateway extends TableGateway
{
    private string $suffix;

    /** @var string */
    private const COMMON_DELIMITERS = '-_';

    /**
     * @param string $suffix
     * @param T|null $rowGatewayPrototype
     * @param Connection|null $connection
     * @param RowGatewayHydrator|null $rowGatewayHydrator
     */
    public function __construct(
        string $suffix,
        ?RowGateway $rowGatewayPrototype = null,
        ?Connection $connection = null,
        ?RowGatewayHydrator $rowGatewayHydrator = null
    ) {
        $suffix = $this->normalizeSuffix($this->trimTablePart($suffix));

        if ($suffix === '') {
            throw new InvalidArgumentException('Invalid suffix.');
        }

        $this->suffix = $suffix;

        parent::__construct($rowGatewayPrototype, $connection, $rowGatewayHydrator);
    }

    private function normalizeSuffix(string $suffix): string
    {
        return strtr($suffix, self::COMMON_DELIMITERS, self::DELIMITER);
    }

    private function trimTablePart(string $part): string
    {
        return trim($part, self::COMMON_DELIMITERS);
    }

    protected function determineTableName(): string
    {
        return sprintf(
            '%s%s%s',
            $this->trimTablePart(parent::determineTableName()),
            self::DELIMITER,
            $this->suffix
        );
    }

    final public function getSuffix(): string
    {
        return $this->suffix;
    }
}
