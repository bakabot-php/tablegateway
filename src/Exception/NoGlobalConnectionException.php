<?php

declare(strict_types = 1);

namespace Bakabot\TableGateway\Exception;

use Bakabot\TableGateway\GlobalConnection;
use LogicException;

final class NoGlobalConnectionException extends LogicException
{
    /** @var string */
    public $message = 'No global connection has been set. Register one using ' . GlobalConnection::class . '::set().';
}
