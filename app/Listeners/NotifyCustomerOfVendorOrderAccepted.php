<?php

namespace App\Listeners;

use App\Events\VendorOrderAccepted;
use App\Jobs\SendOrderStatusSms;

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
    }
}
