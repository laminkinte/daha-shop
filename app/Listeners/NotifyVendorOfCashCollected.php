<?php

namespace App\Listeners;

use App\Events\CashCollected;
use App\Mail\CashCollectedMail;
use App\Notifications\InAppAlert;
use Illuminate\Support\Facades\Mail;

class NotifyVendorOfCashCollected
{
    public function handle(CashCollected $event): void
    {
        $vendorOrder = $event->reconciliation->vendorOrder;
        $vendor = $vendorOrder->vendor;

        // Vendor-facing, non-urgent accounting update - vendors already
        // check their dashboard regularly, so this stays email + in-app.
        $vendor->user->notify(new InAppAlert(
            title: 'Cash collected',
            message: "Cash collected for order #{$vendorOrder->order->order_number}. It is now pending reconciliation before payout.",
            url: route('vendor.orders'),
        ));

        if ($vendor->user->hasRealEmail()) {
            Mail::to($vendor->user->email)->queue(new CashCollectedMail($vendorOrder));
        }
    }
}
