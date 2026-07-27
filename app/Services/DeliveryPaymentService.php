<?php

namespace App\Services;

use App\Enums\DeliveryPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Models\DeliveryPayment;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeliveryPaymentService
{
    public function __construct(private OpayClient $opay) {}

    public function initialize(VendorOrder $vendorOrder, string $method, ?string $phone = null): DeliveryPayment
    {
        $existing = $vendorOrder->deliveryPayment;
        if ($existing && in_array($existing->status, [DeliveryPaymentStatus::Pending, DeliveryPaymentStatus::Paid], true)) {
            return $existing;
        }

        $payment = DeliveryPayment::create([
            'vendor_order_id' => $vendorOrder->id,
            'reference' => 'delivery_'.$vendorOrder->id.'_'.Str::random(16),
            'amount' => $vendorOrder->cashDueAtDelivery(),
            'method' => $method,
            'customer_phone' => $phone,
        ]);

        $data = $this->opay->initialize(
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

        $payment->update(['cashier_url' => $data['cashierUrl'] ?? null]);

        return $payment->fresh();
    }

    public function verifyAndComplete(string $reference, ?string $proofPath = null): ?DeliveryPayment
    {
        $payment = DeliveryPayment::where('reference', $reference)->with('vendorOrder')->first();
        if (! $payment || $payment->status === DeliveryPaymentStatus::Paid) {
            return $payment;
        }

        $data = $this->opay->verifyTransaction($reference);
        if (! $this->opay->transactionSucceeded($data)) {
            $payment->update(['status' => DeliveryPaymentStatus::Failed]);
            return $payment->fresh();
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