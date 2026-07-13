<?php

namespace App\Models;

use App\Enums\FulfillmentMethod;
use App\Enums\VendorOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VendorOrder extends Model
{
    protected $fillable = [
        'order_id', 'vendor_id', 'delivery_agent_id', 'vendor_payout_id', 'status',
        'fulfillment_method', 'items_subtotal', 'delivery_fee', 'cash_collected', 'delivery_attempts',
        'failure_reason', 'proof_of_delivery_path',
        'accepted_at', 'packed_at', 'ready_for_pickup_at', 'out_for_delivery_at', 'delivered_at', 'picked_up_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorOrderStatus::class,
            'fulfillment_method' => FulfillmentMethod::class,
            'accepted_at' => 'datetime',
            'packed_at' => 'datetime',
            'ready_for_pickup_at' => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'picked_up_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function deliveryAgent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(VendorPayout::class, 'vendor_payout_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cashReconciliation(): HasOne
    {
        return $this->hasOne(CashReconciliation::class);
    }

    public function codTotal(): int
    {
        return $this->items_subtotal + $this->delivery_fee;
    }

    /**
     * The delivery fee is prepaid via OPay, so the cash an agent or vendor
     * should actually collect at delivery/pickup only ever covers the items.
     */
    public function cashDueAtDelivery(): int
    {
        return $this->items_subtotal;
    }

    public function isPickup(): bool
    {
        return $this->fulfillment_method === FulfillmentMethod::Pickup;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            VendorOrderStatus::Delivered,
            VendorOrderStatus::PickedUp,
            VendorOrderStatus::Failed,
            VendorOrderStatus::Cancelled,
            VendorOrderStatus::Rejected,
        ], true);
    }
}
