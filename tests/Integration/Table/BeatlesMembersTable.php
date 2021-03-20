<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Integration\Table;

use Bakabot\TableGateway\TableGateway;
use Doctrine\DBAL\Schema\Table;

final class BeatlesMembersTable extends TableGateway
{
    protected function getSeedData(): array
    {
        return [
            [
                'name' => 'John Lennon',
                'is_best_beatle' => true,
            ],
            [
                'name' => 'Paul McCartney',
                'is_best_beatle' => false,
            ],
            [
                'name' => 'George Harrison',
                'is_best_beatle' => false,
            ],
            [
                'name' => 'Ringo Starr',
                'is_best_beatle' => false,
            ],
        ];
    }

    protected function getTableDefinition(): Table
    {
        $table = parent::getTableDefinition();
        $table->addColumn('name', 'string');
        $table->addColumn('is_best_beatle', 'boolean', ['default' => false]);

        return $table;
    }
}
