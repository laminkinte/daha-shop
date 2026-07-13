<?php

namespace App\Enums;

enum DeliveryFeePaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
}
