<?php

namespace App\Listeners;

use App\Events\VendorOrderRejected;
use App\Mail\VendorOrderRejectedMail;
use App\Notifications\InAppAlert;
use Illuminate\Support\Facades\Mail;

class NotifyCustomerOfVendorOrderRejected
{
    public function handle(VendorOrderRejected $event): void
    {
        $vendorOrder = $event->vendorOrder;
        $order = $vendorOrder->order;

        // Only one item in a multi-vendor order is affected (and refunded to
        // stock, no charge) - less severe than the whole order being
        // rejected/cancelled, so this stays email + in-app only.
        $order->user->notify(new InAppAlert(
            title: 'Part of your order could not be fulfilled',
            message: "{$vendorOrder->vendor->business_name} could not fulfil part of your order #{$order->order_number} ({$vendorOrder->failure_reason}). That item has been refunded to stock.",
            url: route('storefront.orders.show', $order),
        ));

        if ($order->user->hasRealEmail()) {
            Mail::to($order->user->email)->queue(new VendorOrderRejectedMail($vendorOrder));
        }
    }
}
