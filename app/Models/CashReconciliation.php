<?php

namespace App\Models;

use App\Enums\ReconciliationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashReconciliation extends Model
{
    protected $fillable = [
        'delivery_agent_id', 'vendor_order_id', 'amount_expected', 'amount_collected',
        'denomination_notes', 'status', 'remitted_amount', 'remitted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReconciliationStatus::class,
            'remitted_at' => 'datetime',
        ];
    }

    public function deliveryAgent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class);
    }

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    public function shortfall(): int
    {
        return max(0, $this->amount_expected - $this->amount_collected);
    }
}
