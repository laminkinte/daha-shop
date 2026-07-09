<?php

namespace App\Listeners;

use App\Events\VendorPayoutProcessed;
use App\Jobs\SendOrderStatusSms;

class NotifyVendorOfPayout
{
    public function handle(VendorPayoutProcessed $event): void
    {
        $payout = $event->payout;
        $nairaAmount = number_format($payout->total_amount / 100);

        SendOrderStatusSms::dispatch(
            $payout->vendor->business_phone,
            "Your Daha Shop payout of ₦{$nairaAmount} for {$payout->period_start->format('M j')}–{$payout->period_end->format('M j')} has been paid."
        );
    }
}
