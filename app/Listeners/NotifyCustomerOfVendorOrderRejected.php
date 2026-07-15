<?php

namespace App\Listeners;

use App\Events\VendorOrderRejected;
use App\Jobs\SendOrderStatusSms;
use App\Mail\VendorOrderRejectedMail;
use Illuminate\Support\Facades\Mail;

class NotifyCustomerOfVendorOrderRejected
{
    public function handle(VendorOrderRejected $event): void
    {
        $vendorOrder = $event->vendorOrder;
        $order = $vendorOrder->order;

        SendOrderStatusSms::dispatch(
            $order->address->phone,
            "{$vendorOrder->vendor->business_name} could not fulfil part of your order #{$order->order_number} ({$vendorOrder->failure_reason}). That item has been refunded to stock."
        );

        if ($order->user->hasRealEmail()) {
            Mail::to($order->user->email)->queue(new VendorOrderRejectedMail($vendorOrder));
        }
    }
}
