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
        private TestPaymentGatewayClient $test,
    ) {}

    public function client(PaymentGateway $gateway): PaymentGatewayClient
    {
        return match ($gateway) {
            PaymentGateway::Paystack => $this->paystack,
            PaymentGateway::Opay => $this->opay,
            PaymentGateway::Monnify => $this->monnify,
            PaymentGateway::PalmPay => $this->palmPay,
            PaymentGateway::Kuda => $this->kuda,
            // Never reachable outside local/testing, even if a form is
            // tampered with to submit gateway=test - see TestPaymentGatewayClient.
            PaymentGateway::Test => app()->environment(['local', 'testing'])
                ? $this->test
                : throw new InvalidArgumentException('Unsupported payment gateway.'),
            default => throw new InvalidArgumentException('Unsupported payment gateway.'),
        };
    }
}