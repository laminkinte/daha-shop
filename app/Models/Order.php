<?php

namespace App\Models;

use App\Enums\ConfirmationStatus;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'address_id', 'status', 'confirmation_status',
        'items_subtotal', 'delivery_fee_total', 'cod_amount_expected', 'cod_amount_collected',
        'delivery_attempts', 'confirmed_at', 'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'confirmation_status' => ConfirmationStatus::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function isFullyResolved(): bool
    {
        return $this->vendorOrders->every(
            fn (VendorOrder $vendorOrder) => $vendorOrder->isTerminal()
        );
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }
}
