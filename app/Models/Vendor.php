<?php

namespace App\Models;

use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'user_id', 'business_name', 'slug', 'business_phone', 'business_address',
        'state_id', 'lga_id', 'cac_number', 'status',
        'bank_name', 'bank_account_number', 'bank_account_name', 'approved_at',
        'id_document_type', 'id_document_path', 'selfie_path',
        'id_document_rejection_reason', 'selfie_rejection_reason', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'approved_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function deliveryFees(): HasMany
    {
        return $this->hasMany(DeliveryFee::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(VendorSubscription::class);
    }

    public function activeSubscription(): ?VendorSubscription
    {
        return $this->subscriptions()
            ->where('status', \App\Enums\SubscriptionStatus::Active)
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->first();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function isApproved(): bool
    {
        return $this->status === VendorStatus::Approved;
    }

    public function needsIdDocumentRetake(): bool
    {
        return $this->id_document_rejection_reason !== null;
    }

    public function needsSelfieRetake(): bool
    {
        return $this->selfie_rejection_reason !== null;
    }
}
