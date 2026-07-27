<?php

namespace App\Models;

use App\Enums\DeliveryPaymentStatus;
use App\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPayment extends Model
{
    protected $fillable = ['vendor_order_id', 'gateway', 'reference', 'amount', 'method', 'status', 'customer_phone', 'cashier_url', 'paid_at'];

    protected function casts(): array
    {
        return ['status' => DeliveryPaymentStatus::class, 'gateway' => PaymentGateway::class, 'paid_at' => 'datetime'];
    }

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }
}