<?php

namespace App\Listeners;

use App\Events\OrderRejected;
use App\Jobs\SendOrderStatusSms;
use App\Mail\OrderRejectedMail;
use Illuminate\Support\Facades\Mail;

class NotifyCustomerOfOrderRejected
{
    public function handle(OrderRejected $event): void
    {
        $order = $event->order;

        SendOrderStatusSms::dispatch(
            $order->address->phone,
            "Your Daha Shop order #{$order->order_number} was rejected ({$order->cancellation_reason}). Any reserved stock has been released."
        );

        if ($order->user->hasRealEmail()) {
            Mail::to($order->user->email)->queue(new OrderRejectedMail($order));
        }
    }
}
