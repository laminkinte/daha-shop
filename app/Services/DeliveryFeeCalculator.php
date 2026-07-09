<?php

namespace App\Services;

use App\Exceptions\DeliveryNotAvailableException;
use App\Models\Address;
use App\Models\DeliveryFee;
use App\Models\DeliveryZone;
use App\Models\Vendor;

class DeliveryFeeCalculator
{
    /**
     * @throws DeliveryNotAvailableException
     */
    public function feeFor(Address $address, Vendor $vendor): int
    {
        $zone = DeliveryZone::where('state_id', $address->state_id)
            ->where(function ($query) use ($address) {
                $query->where('lga_id', $address->lga_id)->orWhereNull('lga_id');
            })
            ->orderByRaw('lga_id is null')
            ->first();

        if (! $zone) {
            throw new DeliveryNotAvailableException;
        }

        $fee = DeliveryFee::where('delivery_zone_id', $zone->id)
            ->where(function ($query) use ($vendor) {
                $query->where('vendor_id', $vendor->id)->orWhereNull('vendor_id');
            })
            ->orderByRaw('vendor_id is null')
            ->first();

        if (! $fee) {
            throw new DeliveryNotAvailableException;
        }

        return $fee->fee;
    }
}
