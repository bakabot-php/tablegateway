<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway;

use ReflectionClass;

/**
 * @internal
 * @psalm-internal Bakabot\TableGateway
 */
trait ClassBasedTableNameTrait
{
    private ?ReflectionClass $reflection = null;

    private function getReflectionClass(): ReflectionClass
    {
        if (!isset($this->reflection)) {
            $this->reflection = new ReflectionClass(static::class);
        }

        return $this->reflection;
    }

    private function inferTableName(): string
    {
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->getReflectionClass()->getShortName()));
        $tableName = str_replace('_table', '', $tableName);

        return $tableName;
    }
}
