<?php

namespace App\Listeners;

use App\Events\VendorOrderAccepted;
use App\Mail\VendorOrderAcceptedMail;
use App\Notifications\InAppAlert;
use Illuminate\Support\Facades\Mail;

class NotifyCustomerOfVendorOrderAccepted
{
    public function handle(VendorOrderAccepted $event): void
    {
        $vendorOrder = $event->vendorOrder;
        $order = $vendorOrder->order;

        // Progress-update FYI, not a definitive outcome - the customer
        // already knows the order is confirmed, so this doesn't need SMS.
        $order->user->notify(new InAppAlert(
            title: 'Order being prepared',
            message: "{$vendorOrder->vendor->business_name} has accepted your order #{$order->order_number} and is preparing it.",
            url: route('storefront.orders.show', $order),
        ));

        if ($order->user->hasRealEmail()) {
            Mail::to($order->user->email)->queue(new VendorOrderAcceptedMail($vendorOrder));
        }
    }
}
