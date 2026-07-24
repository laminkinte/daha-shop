<?php

namespace App\Enums;

enum PaymentGateway: string
{
    case Paystack = 'paystack';
    case Opay = 'opay';
    case Monnify = 'monnify';
    case PalmPay = 'palmpay';
    case Kuda = 'kuda';

    public function label(): string
    {
        return match ($this) {
            self::Paystack => 'Paystack',
            self::Opay => 'OPay',
            self::Monnify => 'Monnify',
            self::PalmPay => 'PalmPay',
            self::Kuda => 'Kuda',
        };
    }

    /**
     * Short badge initials shown in the gateway picker - not a reproduction
     * of any brand's real logo/wordmark, just a monogram placeholder.
     */
    public function initials(): string
    {
        return match ($this) {
            self::Paystack => 'PS',
            self::Opay => 'OP',
            self::Monnify => 'MF',
            self::PalmPay => 'PP',
            self::Kuda => 'KU',
        };
    }

    /**
     * Approximate brand-associated color for the picker badge - a tasteful
     * nod to each brand's public identity, not an exact/official color.
     */
    public function badgeColorClass(): string
    {
        return match ($this) {
            self::Paystack => 'bg-[#00C3F7]',
            self::Opay => 'bg-[#1DB157]',
            self::Monnify => 'bg-[#1A73E8]',
            self::PalmPay => 'bg-[#6C2EB9]',
            self::Kuda => 'bg-[#2E1760]',
        };
    }
}
