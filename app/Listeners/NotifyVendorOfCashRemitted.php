<?php

namespace App\Listeners;

use App\Events\CashRemitted;
use App\Mail\CashRemittedMail;
use App\Notifications\InAppAlert;
use Illuminate\Support\Facades\Mail;

class NotifyVendorOfCashRemitted
{
    public function handle(CashRemitted $event): void
    {
        $vendorOrder = $event->reconciliation->vendorOrder;
        $vendor = $vendorOrder->vendor;

        $vendor->user->notify(new InAppAlert(
            title: 'Cash remitted',
            message: "Cash for order #{$vendorOrder->order->order_number} has been remitted and reconciled. It will be included in your next payout.",
            url: route('vendor.orders'),
        ));

        if ($vendor->user->hasRealEmail()) {
            Mail::to($vendor->user->email)->queue(new CashRemittedMail($event->reconciliation));
        }
    }
}
