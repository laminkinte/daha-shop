<?php

namespace App\Enums;

enum DeliveryPaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
}