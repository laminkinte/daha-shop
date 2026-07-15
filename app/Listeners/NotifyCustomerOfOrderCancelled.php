<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Jobs\SendOrderStatusSms;
use App\Mail\OrderCancelledMail;
use Illuminate\Support\Facades\Mail;

class NotifyCustomerOfOrderCancelled
{
    public function handle(OrderCancelled $event): void
    {
        $order = $event->order;

        SendOrderStatusSms::dispatch(
            $order->address->phone,
            "Your Daha Shop order #{$order->order_number} has been cancelled ({$order->cancellation_reason})."
        );

        if ($order->user->hasRealEmail()) {
            Mail::to($order->user->email)->queue(new OrderCancelledMail($order));
        }
    }
}
