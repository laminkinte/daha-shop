<?php

namespace App\Services;

use App\Enums\DeliveryFailureReason;
use App\Enums\OrderStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\VendorOrderStatus;
use App\Events\AgentAssignedToDelivery;
use App\Events\CashCollected;
use App\Events\DeliveryFailed;
use App\Events\VendorOrderAccepted;
use App\Events\VendorOrderRejected;
use App\Models\CashReconciliation;
use App\Models\DeliveryAgent;
use App\Models\VendorOrder;

class VendorOrderService
{
    public function accept(VendorOrder $vendorOrder): void
    {
        $vendorOrder->update([
            'status' => VendorOrderStatus::Accepted,
            'accepted_at' => now(),
        ]);

        VendorOrderAccepted::dispatch($vendorOrder->fresh());
    }

    public function reject(VendorOrder $vendorOrder, string $reason): void
    {
        $vendorOrder->update([
            'status' => VendorOrderStatus::Rejected,
            'failure_reason' => $reason,
        ]);

        $this->restock($vendorOrder);
        $this->finalizeOrderIfResolved($vendorOrder);

        VendorOrderRejected::dispatch($vendorOrder->fresh());
    }

    public function pack(VendorOrder $vendorOrder): void
    {
        $vendorOrder->update([
            'status' => VendorOrderStatus::Packed,
            'packed_at' => now(),
        ]);
    }

    public function assignAgent(VendorOrder $vendorOrder, DeliveryAgent $agent): void
    {
        $vendorOrder->update([
            'status' => VendorOrderStatus::AssignedToAgent,
            'delivery_agent_id' => $agent->id,
        ]);

        AgentAssignedToDelivery::dispatch($vendorOrder->fresh());
    }

    public function markOutForDelivery(VendorOrder $vendorOrder): void
    {
        $vendorOrder->update([
            'status' => VendorOrderStatus::OutForDelivery,
            'out_for_delivery_at' => now(),
        ]);
    }

    public function markDelivered(
        VendorOrder $vendorOrder,
        int $cashCollected,
        ?string $proofOfDeliveryPath = null,
        ?string $denominationNotes = null,
    ): CashReconciliation {
        $vendorOrder->update([
            'status' => VendorOrderStatus::Delivered,
            'delivered_at' => now(),
            'cash_collected' => $cashCollected,
            'proof_of_delivery_path' => $proofOfDeliveryPath,
        ]);

        $expected = $vendorOrder->cashDueAtDelivery();

        $reconciliation = CashReconciliation::create([
            'delivery_agent_id' => $vendorOrder->delivery_agent_id,
            'vendor_order_id' => $vendorOrder->id,
            'amount_expected' => $expected,
            'amount_collected' => $cashCollected,
            'denomination_notes' => $denominationNotes,
            'status' => $cashCollected >= $expected ? ReconciliationStatus::Collected : ReconciliationStatus::Short,
        ]);

        $vendorOrder->order->increment('cod_amount_collected', $cashCollected);

        CashCollected::dispatch($reconciliation);

        $this->finalizeOrderIfResolved($vendorOrder);

        return $reconciliation;
    }

    public function markReadyForPickup(VendorOrder $vendorOrder): void
    {
        $vendorOrder->update([
            'status' => VendorOrderStatus::ReadyForPickup,
            'ready_for_pickup_at' => now(),
        ]);
    }

    /**
     * The vendor collects cash directly from the customer at pickup, so
     * unlike markDelivered() there's no delivery agent involved and no
     * CashReconciliation row - the vendor already has the money in hand and
     * doesn't need it remitted back via a payout.
     */
    public function markPickedUp(VendorOrder $vendorOrder, int $cashCollected): void
    {
        $vendorOrder->update([
            'status' => VendorOrderStatus::PickedUp,
            'picked_up_at' => now(),
            'cash_collected' => $cashCollected,
        ]);

        $vendorOrder->order->increment('cod_amount_collected', $cashCollected);

        $this->finalizeOrderIfResolved($vendorOrder);
    }

    public function markFailed(VendorOrder $vendorOrder, DeliveryFailureReason $reason): void
    {
        $vendorOrder->increment('delivery_attempts');
        $vendorOrder->update(['failure_reason' => $reason->value]);

        if ($vendorOrder->delivery_attempts >= config('markethub.max_delivery_attempts')) {
            $vendorOrder->update(['status' => VendorOrderStatus::Failed]);
            $this->restock($vendorOrder);
            $this->finalizeOrderIfResolved($vendorOrder);
        } else {
            $vendorOrder->update(['status' => VendorOrderStatus::Packed]);
        }

        DeliveryFailed::dispatch($vendorOrder->fresh());
    }

    private function restock(VendorOrder $vendorOrder): void
    {
        foreach ($vendorOrder->items as $item) {
            if ($item->product_variant_id) {
                $item->variant->increment('stock', $item->quantity);
            } else {
                $item->product->increment('stock', $item->quantity);
            }
        }
    }

    private function finalizeOrderIfResolved(VendorOrder $vendorOrder): void
    {
        $order = $vendorOrder->order()->with('vendorOrders')->first();

        if (! $order->isFullyResolved()) {
            return;
        }

        $anyFulfilled = $order->vendorOrders->contains(
            fn (VendorOrder $vo) => in_array($vo->status, [VendorOrderStatus::Delivered, VendorOrderStatus::PickedUp], true)
        );

        $order->update([
            'status' => $anyFulfilled ? OrderStatus::Completed : OrderStatus::Cancelled,
        ]);
    }
}
