<?php

namespace App\Services;

use App\Enums\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function __construct(
        private PaystackClient $paystack,
        private OpayClient $opay,
        private MonnifyClient $monnify,
        private PalmPayClient $palmPay,
        private KudaClient $kuda,
    ) {}

    public function client(PaymentGateway $gateway): PaymentGatewayClient
    {
        return match ($gateway) {
            PaymentGateway::Paystack => $this->paystack,
            PaymentGateway::Opay => $this->opay,
            PaymentGateway::Monnify => $this->monnify,
            PaymentGateway::PalmPay => $this->palmPay,
            PaymentGateway::Kuda => $this->kuda,
            default => throw new InvalidArgumentException('Unsupported payment gateway.'),
        };
    }
}