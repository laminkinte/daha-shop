<?php

namespace App\Services;

use App\Enums\PayoutStatus;
use App\Enums\VendorOrderStatus;
use App\Events\VendorPayoutProcessed;
use App\Exceptions\NothingToPayoutException;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayout;
use Carbon\Carbon;

class PayoutService
{
    /**
     * @throws NothingToPayoutException
     */
    public function generateForVendor(Vendor $vendor, Carbon $periodStart, Carbon $periodEnd): VendorPayout
    {
        $eligible = VendorOrder::where('vendor_id', $vendor->id)
            ->where('status', VendorOrderStatus::Delivered)
            ->whereNull('vendor_payout_id')
            ->whereBetween('delivered_at', [$periodStart, $periodEnd])
            ->get();

        if ($eligible->isEmpty()) {
            throw new NothingToPayoutException;
        }

        $payout = VendorPayout::create([
            'vendor_id' => $vendor->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_amount' => $eligible->sum('items_subtotal'),
            'status' => PayoutStatus::Pending,
        ]);

        VendorOrder::whereIn('id', $eligible->pluck('id'))->update(['vendor_payout_id' => $payout->id]);

        return $payout;
    }

    public function markPaid(VendorPayout $payout, string $reference): void
    {
        $payout->update([
            'status' => PayoutStatus::Paid,
            'reference' => $reference,
            'paid_at' => now(),
        ]);

        VendorPayoutProcessed::dispatch($payout->fresh());
    }
}
