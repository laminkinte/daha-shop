<?php

namespace App\Services;

use App\Enums\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function __construct(
        private PaystackClient $paystack,
        private OpayClient $opay,
    ) {}

    public function client(PaymentGateway $gateway): PaymentGatewayClient
    {
        return match ($gateway) {
            PaymentGateway::Paystack => $this->paystack,
            PaymentGateway::Opay => $this->opay,
            default => throw new InvalidArgumentException('Unsupported payment gateway.'),
        };
    }
}