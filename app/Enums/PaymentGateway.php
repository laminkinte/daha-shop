<?php

namespace App\Enums;

enum PaymentGateway: string
{
    case Paystack = 'paystack';
    case Opay = 'opay';

    public function label(): string
    {
        return match ($this) {
            self::Paystack => 'Paystack',
            self::Opay => 'OPay',
        };
    }
}
