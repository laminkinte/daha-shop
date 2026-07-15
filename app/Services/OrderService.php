<?php

namespace App\Services;

use App\Enums\ConfirmationStatus;
use App\Enums\OrderStatus;
use App\Events\OrderCancelled;
use App\Events\OrderConfirmed;
use App\Events\OrderRejected;
use App\Models\Order;

class OrderService
{
    public function confirmFromOtp(Order $order): void
    {
        if (! $order->deliveryFeePaid()) {
            return;
        }

        if ($order->cod_amount_expected > config('markethub.max_cod_auto_confirm_amount')) {
            $order->update(['confirmation_status' => ConfirmationStatus::PendingAdminReview]);

            return;
        }

        $this->confirm($order);
    }

    public function adminApprove(Order $order): void
    {
        $this->confirm($order);
    }

    public function confirm(Order $order): void
    {
        $order->update([
            'status' => OrderStatus::Processing,
            'confirmation_status' => ConfirmationStatus::Confirmed,
            'confirmed_at' => now(),
        ]);

        OrderConfirmed::dispatch($order->fresh());
    }

    public function reject(Order $order, string $reason): void
    {
        $order->update([
            'status' => OrderStatus::Rejected,
            'confirmation_status' => ConfirmationStatus::Rejected,
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        $this->restock($order);

        OrderRejected::dispatch($order->fresh());
    }

    public function cancel(Order $order, string $reason): void
    {
        $order->update([
            'status' => OrderStatus::Cancelled,
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        $this->restock($order);

        OrderCancelled::dispatch($order->fresh());
    }

    private function restock(Order $order): void
    {
        foreach ($order->vendorOrders as $vendorOrder) {
            foreach ($vendorOrder->items as $item) {
                if ($item->product_variant_id) {
                    $item->variant->increment('stock', $item->quantity);
                } else {
                    $item->product->increment('stock', $item->quantity);
                }
            }
        }
    }
}
