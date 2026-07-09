<?php

namespace App\Exceptions;

use Exception;

class DeliveryNotAvailableException extends Exception
{
    protected $message = 'Delivery is not yet available to this location.';
}
