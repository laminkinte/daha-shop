<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Jobs\SendOrderStatusSms;

class NotifyCustomerOfOrderConfirmed
{
    public function handle(OrderConfirmed $event): void
    {
        $order = $event->order;

        SendOrderStatusSms::dispatch(
            $order->address->phone,
            "Your Daha Shop order #{$order->order_number} is confirmed and being processed."
        );
    }
}
