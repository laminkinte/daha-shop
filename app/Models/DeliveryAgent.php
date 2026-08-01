<?php

namespace App\Models;

use App\Enums\AgentAvailability;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryAgent extends Model
{
    protected $fillable = ['user_id', 'state_id', 'lga_id', 'vehicle_type', 'availability', 'current_lat', 'current_lng', 'location_updated_at'];

    protected function casts(): array
    {
        return [
            'availability' => AgentAvailability::class,
            'current_lat' => 'decimal:7',
            'current_lng' => 'decimal:7',
            'location_updated_at' => 'datetime',
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

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function cashReconciliations(): HasMany
    {
        return $this->hasMany(CashReconciliation::class);
    }
}
