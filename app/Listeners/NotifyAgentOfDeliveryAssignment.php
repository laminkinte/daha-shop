<?php

namespace App\Listeners;

use App\Events\AgentAssignedToDelivery;
use App\Jobs\SendOrderStatusSms;
use App\Mail\AgentAssignedToDeliveryMail;
use Illuminate\Support\Facades\Mail;

class NotifyAgentOfDeliveryAssignment
{
    public function handle(AgentAssignedToDelivery $event): void
    {
        $vendorOrder = $event->vendorOrder;
        $agent = $vendorOrder->deliveryAgent;

        SendOrderStatusSms::dispatch(
            $agent->user->phone,
            "Daha Shop: you've been assigned to deliver order #{$vendorOrder->order->order_number} from {$vendorOrder->vendor->business_name}."
        );

        if ($agent->user->hasRealEmail()) {
            Mail::to($agent->user->email)->queue(new AgentAssignedToDeliveryMail($vendorOrder));
        }
    }
}
