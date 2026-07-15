<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Jobs\SendOrderStatusSms;
use App\Mail\OrderConfirmedMail;
use Illuminate\Support\Facades\Mail;

class NotifyCustomerOfOrderConfirmed
{
    public function handle(OrderConfirmed $event): void
    {
        $order = $event->order;

        SendOrderStatusSms::dispatch(
            $order->address->phone,
            "Your Daha Shop order #{$order->order_number} is confirmed and being processed."
        );

        if ($order->user->hasRealEmail()) {
            Mail::to($order->user->email)->queue(new OrderConfirmedMail($order));
        }
    }
}
