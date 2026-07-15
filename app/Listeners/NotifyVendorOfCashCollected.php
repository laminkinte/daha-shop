<?php

namespace App\Listeners;

use App\Events\CashCollected;
use App\Jobs\SendOrderStatusSms;
use App\Mail\CashCollectedMail;
use Illuminate\Support\Facades\Mail;

class NotifyVendorOfCashCollected
{
    public function handle(CashCollected $event): void
    {
        $vendorOrder = $event->reconciliation->vendorOrder;
        $vendor = $vendorOrder->vendor;

        SendOrderStatusSms::dispatch(
            $vendor->business_phone,
            "Cash collected for order #{$vendorOrder->order->order_number}. It is now pending reconciliation before payout."
        );

        if ($vendor->user->hasRealEmail()) {
            Mail::to($vendor->user->email)->queue(new CashCollectedMail($vendorOrder));
        }
    }
}
