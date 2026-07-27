<?php

namespace App\Services;

use App\Enums\DeliveryPaymentStatus;
use App\Enums\PaymentGateway;
use App\Enums\VendorOrderStatus;
use App\Models\DeliveryPayment;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeliveryPaymentService
{
    public function __construct(private PaymentGatewayManager $gateways) {}

    public function initialize(VendorOrder $vendorOrder, string $method, ?string $phone = null, PaymentGateway $gateway = PaymentGateway::Opay): DeliveryPayment
    {
        $existing = $vendorOrder->deliveryPayment;

        if ($existing && $existing->status === DeliveryPaymentStatus::Paid) {
            return $existing;
        }

        // Picking a different gateway than the stuck/pending attempt is a
        // deliberate signal to retry with it (e.g. switching away from a
        // misconfigured OPay to the local Test gateway) - otherwise a
        // pending payment against a gateway that can never resolve (bad
        // credentials, downtime) would trap the order forever with no way
        // out except manual DB cleanup.
        if ($existing && $existing->status === DeliveryPaymentStatus::Pending && $existing->gateway === $gateway) {
            return $existing;
        }

        $attributes = [
            'vendor_order_id' => $vendorOrder->id,
            'gateway' => $gateway,
            'reference' => 'delivery_'.$vendorOrder->id.'_'.Str::random(16),
            'amount' => $vendorOrder->cashDueAtDelivery(),
            'method' => $method,
            'status' => DeliveryPaymentStatus::Pending,
            'customer_phone' => $phone,
            'cashier_url' => null,
            'paid_at' => null,
        ];

        // Reuse the same row (there's only ever one DeliveryPayment per
        // VendorOrder via the hasOne relation) instead of inserting a second
        // row that the relation would then have to arbitrarily pick between.
        $payment = $existing ? tap($existing)->update($attributes) : DeliveryPayment::create($attributes);

        $client = $this->gateways->client($gateway);

        if ($gateway === PaymentGateway::Opay) {
            $data = $client->initialize(
                reference: $payment->reference,
                amountKobo: $payment->amount,
                returnUrl: route('delivery-payment.callback'),
                context: [
                    'callbackUrl' => route('webhooks.opay'),
                    'userInfo' => [
                        'userId' => (string) $vendorOrder->order->user_id,
                        'userName' => $vendorOrder->order->user->name,
                        'userMobile' => $phone ?: $vendorOrder->order->address->phone,
                        'userEmail' => $vendorOrder->order->user->email,
                    ],
                ],
            );
        } else {
            $data = $client->initialize(
                reference: $payment->reference,
                amountKobo: $payment->amount,
                returnUrl: route('delivery-payment.callback'),
                context: [
                    'email' => $vendorOrder->order->user->email,
                    'customerName' => $vendorOrder->order->user->name,
                    'metadata' => [
                        'vendor_order_id' => $vendorOrder->id,
                    ],
                    // Real gateways here (Paystack et al) already have their
                    // own hosted checkout the customer has to act on - only
                    // the Test gateway needs telling that a *different*
                    // device (the customer's) is expected to confirm, not
                    // the same one that clicked Start Payment.
                    'requiresManualConfirmation' => $gateway === PaymentGateway::Test,
                ],
            );
        }

        $payment->update(['cashier_url' => $data['cashierUrl'] ?? $data['authorization_url'] ?? $data['checkoutUrl'] ?? null]);

        return $payment->fresh();
    }

    public function verifyAndComplete(string $reference, ?string $proofPath = null): ?DeliveryPayment
    {
        $payment = DeliveryPayment::where('reference', $reference)->with('vendorOrder')->first();
        if (! $payment || $payment->status === DeliveryPaymentStatus::Paid) {
            return $payment;
        }

        $client = $this->gateways->client($payment->gateway);

        // This is called from a 5s wire:poll loop (both agent and vendor
        // screens), so a transient/misconfigured-gateway error here must not
        // bubble up into a 500 on every poll tick - leave the payment
        // Pending and let the next poll (or a manual gateway switch via
        // initialize()) try again.
        try {
            $data = $client->verifyTransaction($reference);
        } catch (\Throwable $e) {
            report($e);

            return $payment;
        }

        if (! $client->transactionSucceeded($data)) {
            // Not yet succeeded is not the same as failed - a real gateway
            // reporting "still initial/pending" here used to get marked
            // Failed outright, which is wrong (and would have made every
            // still-outstanding QR scan look like a dead end on the very
            // next poll). Leave it Pending; the next poll tries again.
            return $payment;
        }

        DB::transaction(function () use ($payment, $proofPath) {
            $payment->update(['status' => DeliveryPaymentStatus::Paid, 'paid_at' => now()]);
            $vendorOrder = $payment->vendorOrder()->lockForUpdate()->first();
            $isPickup = $vendorOrder->isPickup();
            $targetStatus = $isPickup ? VendorOrderStatus::PickedUp : VendorOrderStatus::Delivered;

            if ($vendorOrder->status !== $targetStatus) {
                $vendorOrder->update([
                    'status' => $targetStatus,
                    'delivered_at' => $isPickup ? null : now(),
                    'picked_up_at' => $isPickup ? now() : null,
                    'cash_collected' => 0,
                    'proof_of_delivery_path' => $proofPath ?: $vendorOrder->proof_of_delivery_path,
                ]);
                app(VendorOrderService::class)->finalizeOrderIfResolvedForPayment($vendorOrder);
            }
        });

        return $payment->fresh();
    }
}