<?php

namespace App\Models;

use App\Enums\PaymentGateway;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSubscription extends Model
{
    protected $fillable = [
        'vendor_id', 'gateway', 'plan', 'amount', 'status', 'reference',
        'paid_at', 'starts_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'gateway' => PaymentGateway::class,
            'plan' => SubscriptionPlan::class,
            'status' => SubscriptionStatus::class,
            'paid_at' => 'datetime',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }
}
