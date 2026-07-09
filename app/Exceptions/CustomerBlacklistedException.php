<?php

namespace App\Exceptions;

use Exception;

class CustomerBlacklistedException extends Exception
{
    protected $message = 'This phone number is not eligible to place cash-on-delivery orders. Please contact support.';
}
