<?php

namespace App\Listeners;

use App\Events\DeliveryFailed;
use App\Jobs\SendOrderStatusSms;

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
    }
}
