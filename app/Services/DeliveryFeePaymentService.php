<?php

namespace App\Services;

use App\Enums\DeliveryFeePaymentStatus;
use App\Enums\PaymentGateway;
use App\Models\DeliveryFeePayment;
use App\Models\Order;
use Illuminate\Support\Str;

class DeliveryFeePaymentService
{
    public function __construct(private PaymentGatewayManager $gateways) {}

    /**
     * Create a pending delivery-fee payment and start a transaction with the
     * chosen gateway. Returns the URL the customer should be redirected to.
     */
    public function initialize(Order $order, PaymentGateway $gateway, string $returnUrl): string
    {
        $reference = 'delfee_'.$order->id.'_'.Str::random(16);

        $payment = DeliveryFeePayment::create([
            'order_id' => $order->id,
            'gateway' => $gateway,
            'reference' => $reference,
            'amount' => $order->delivery_fee_total,
            'status' => DeliveryFeePaymentStatus::Pending,
        ]);

        $client = $this->gateways->client($gateway);

        if ($gateway === PaymentGateway::Opay) {
            $opayReturnUrl = $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'reference='.$reference;

            $data = $client->initialize(
                reference: $reference,
                amountKobo: $payment->amount,
                returnUrl: $opayReturnUrl,
                context: [
                    'callbackUrl' => route('webhooks.opay'),
                    'userInfo' => [
                        'userId' => (string) $order->user_id,
                        'userName' => $order->user->name,
                        'userMobile' => $order->address->phone,
                        'userEmail' => $order->user->email,
                    ],
                ],
            );

            return $data['cashierUrl'];
        }

        $data = $client->initialize(
            reference: $reference,
            amountKobo: $payment->amount,
            returnUrl: $returnUrl,
            context: [
                'email' => $order->user->email,
                'customerName' => $order->user->name,
                'metadata' => [
                    'order_id' => $order->id,
                ],
            ],
        );

        return $data['authorization_url'] ?? $data['checkoutUrl'];
    }

    /**
     * Verify a reference against its gateway and mark the payment (and
     * order) paid if it succeeded. Safe to call more than once for the same
     * reference - from both the browser callback and the webhook.
     */
    public function verifyAndActivate(string $reference): ?DeliveryFeePayment
    {
        $payment = DeliveryFeePayment::where('reference', $reference)->first();

        if (! $payment || $payment->status === DeliveryFeePaymentStatus::Paid) {
            return $payment;
        }

        $client = $this->gateways->client($payment->gateway);

        $data = $client->verifyTransaction($reference);

        if (! $client->transactionSucceeded($data)) {
            $payment->update(['status' => DeliveryFeePaymentStatus::Failed]);

            return $payment;
        }

        $payment->update(['status' => DeliveryFeePaymentStatus::Paid, 'paid_at' => now()]);
        $payment->order->update(['delivery_fee_paid_at' => now()]);

        return $payment->fresh();
    }
}
