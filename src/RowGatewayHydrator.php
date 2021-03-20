<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway;

use Closure;
use Doctrine\DBAL\Connection;

final class RowGatewayHydrator
{
    private Closure $hydrator;

    private function __construct(Closure $hydrator)
    {
        $this->hydrator = $hydrator->bindTo(null, RowGateway::class);
    }

    /**
     * @param Connection $connection
     * @param array<string, string> $columnTypes
     * @return self
     */
    public static function factory(Connection $connection, array $columnTypes): self
    {
        $valueConverter = static function (string $column, mixed $value) use ($columnTypes, $connection): mixed {
            return $connection->convertToPHPValue($value, $columnTypes[$column]);
        };

        $hydrator = function (array $data, bool $convertValues) use ($columnTypes, $valueConverter): void {
            /** @var array<string, mixed> $data */
            $this->id = $id = (int) $data['id'];
            $this->rowGatewayData = ['id' => $id];

            unset($data['id']);

            /** @var mixed $value */
            foreach ($data as $column => $value) {
                if ($convertValues) {
                    /** @var mixed $value */
                    $value = $valueConverter($column, $value);
                }

                $this->rowGatewayData[$column] = $value;

                if (isset($columnTypes[$column])) {
                    if (property_exists($this, $column)) {
                        $this->{$column} = $value;
                        continue;
                    }

                    if (str_contains($column, '_')) {
                        $camelCasedColumn = lcfirst(str_replace('_', '', ucwords($column, '_')));

                        if (property_exists($this, $camelCasedColumn)) {
                            $this->{$camelCasedColumn} = $value;
                        }
                    }
                }
            }
        };

        return new self($hydrator);
    }

    public function hydrate(RowGateway $rowGateway, array $data, bool $convertValues): void
    {
        $this->hydrator->call($rowGateway, $data, $convertValues);
    }
}
