<?php

namespace App\Models;

use App\Enums\DeliveryFeePaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryFeePayment extends Model
{
    protected $fillable = [
        'order_id', 'reference', 'amount', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryFeePaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
