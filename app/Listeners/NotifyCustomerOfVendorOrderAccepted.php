<?php

namespace App\Listeners;

use App\Events\VendorOrderAccepted;
use App\Jobs\SendOrderStatusSms;
use App\Mail\VendorOrderAcceptedMail;
use Illuminate\Support\Facades\Mail;

class NotifyCustomerOfVendorOrderAccepted
{
    public function handle(VendorOrderAccepted $event): void
    {
        $vendorOrder = $event->vendorOrder;
        $order = $vendorOrder->order;

        SendOrderStatusSms::dispatch(
            $order->address->phone,
            "{$vendorOrder->vendor->business_name} has accepted your order #{$order->order_number} and is preparing it."
        );

        if ($order->user->hasRealEmail()) {
            Mail::to($order->user->email)->queue(new VendorOrderAcceptedMail($vendorOrder));
        }
    }
}
