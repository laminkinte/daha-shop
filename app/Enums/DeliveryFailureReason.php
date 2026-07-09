<?php

namespace App\Enums;

enum DeliveryFailureReason: string
{
    case CustomerUnreachable = 'customer_unreachable';
    case WrongAddress = 'wrong_address';
    case RefusedItem = 'refused_item';
    case NoCash = 'no_cash';
    case Other = 'other';
}
