<?php

namespace App\Exceptions;

use Exception;

class NothingToPayoutException extends Exception
{
    protected $message = 'There are no reconciled orders due for payout in this period.';
}
