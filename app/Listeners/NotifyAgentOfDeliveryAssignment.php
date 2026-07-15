<?php

namespace App\Listeners;

use App\Events\AgentAssignedToDelivery;
use App\Mail\AgentAssignedToDeliveryMail;
use App\Notifications\InAppAlert;
use Illuminate\Support\Facades\Mail;

class NotifyAgentOfDeliveryAssignment
{
    public function handle(AgentAssignedToDelivery $event): void
    {
        $vendorOrder = $event->vendorOrder;
        $agent = $vendorOrder->deliveryAgent;

        $agent->user->notify(new InAppAlert(
            title: 'New delivery assigned',
            message: "You've been assigned to deliver order #{$vendorOrder->order->order_number} from {$vendorOrder->vendor->business_name}.",
            url: route('agent.deliveries.show', $vendorOrder->id),
        ));

        if ($agent->user->hasRealEmail()) {
            Mail::to($agent->user->email)->queue(new AgentAssignedToDeliveryMail($vendorOrder));
        }
    }
}
