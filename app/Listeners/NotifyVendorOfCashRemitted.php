<?php

namespace App\Listeners;

use App\Events\CashRemitted;
use App\Jobs\SendOrderStatusSms;
use App\Mail\CashRemittedMail;
use Illuminate\Support\Facades\Mail;

class NotifyVendorOfCashRemitted
{
    public function handle(CashRemitted $event): void
    {
        $vendorOrder = $event->reconciliation->vendorOrder;
        $vendor = $vendorOrder->vendor;

        SendOrderStatusSms::dispatch(
            $vendor->business_phone,
            "Daha Shop: cash for order #{$vendorOrder->order->order_number} has been remitted and reconciled. It will be included in your next payout."
        );

        if ($vendor->user->hasRealEmail()) {
            Mail::to($vendor->user->email)->queue(new CashRemittedMail($event->reconciliation));
        }
    }
}
