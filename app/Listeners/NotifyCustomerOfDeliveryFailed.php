<?php

namespace App\Listeners;

use App\Events\DeliveryFailed;
use App\Jobs\SendOrderStatusSms;
use App\Mail\DeliveryFailedMail;
use Illuminate\Support\Facades\Mail;

class NotifyCustomerOfDeliveryFailed
{
    public function handle(DeliveryFailed $event): void
    {
        $vendorOrder = $event->vendorOrder;
        $order = $vendorOrder->order;

        SendOrderStatusSms::dispatch(
            $order->address->phone,
            "We could not deliver part of your order #{$order->order_number} ({$vendorOrder->failure_reason}). We will retry or contact you shortly."
        );

        if ($order->user->hasRealEmail()) {
            Mail::to($order->user->email)->queue(new DeliveryFailedMail($vendorOrder));
        }
    }
}
