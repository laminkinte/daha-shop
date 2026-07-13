<?php

namespace App\Services;

use App\Enums\DeliveryFeePaymentStatus;
use App\Models\DeliveryFeePayment;
use App\Models\Order;
use Illuminate\Support\Str;

class DeliveryFeePaymentService
{
    public function __construct(private OpayClient $opay) {}

    /**
     * Create a pending delivery-fee payment and start an OPay Cashier order
     * for it. Returns the cashierUrl the customer should be redirected to.
     */
    public function initialize(Order $order, string $returnUrl): string
    {
        $reference = 'delfee_'.$order->id.'_'.Str::random(16);

        $payment = DeliveryFeePayment::create([
            'order_id' => $order->id,
            'reference' => $reference,
            'amount' => $order->delivery_fee_total,
            'status' => DeliveryFeePaymentStatus::Pending,
        ]);

        $returnUrl .= (str_contains($returnUrl, '?') ? '&' : '?').'reference='.$reference;

        $data = $this->opay->createCashierOrder(
            reference: $reference,
            amountKobo: $payment->amount,
            returnUrl: $returnUrl,
            callbackUrl: route('webhooks.opay'),
            userInfo: [
                'userId' => (string) $order->user_id,
                'userName' => $order->user->name,
                'userMobile' => $order->address->phone,
                'userEmail' => $order->user->email,
            ],
        );

        return $data['cashierUrl'];
    }

    /**
     * Verify a reference against OPay and mark the payment (and order) paid
     * if it succeeded. Safe to call more than once for the same reference -
     * from both the browser callback and the webhook.
     */
    public function verifyAndActivate(string $reference): ?DeliveryFeePayment
    {
        $payment = DeliveryFeePayment::where('reference', $reference)->first();

        if (! $payment || $payment->status === DeliveryFeePaymentStatus::Paid) {
            return $payment;
        }

        $data = $this->opay->queryStatus($reference);

        if (($data['status'] ?? null) !== 'SUCCESS') {
            $payment->update(['status' => DeliveryFeePaymentStatus::Failed]);

            return $payment;
        }

        $payment->update(['status' => DeliveryFeePaymentStatus::Paid, 'paid_at' => now()]);
        $payment->order->update(['delivery_fee_paid_at' => now()]);

        return $payment->fresh();
    }
}
