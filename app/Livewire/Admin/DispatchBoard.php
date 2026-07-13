<?php

namespace App\Livewire\Admin;

use App\Enums\AgentAvailability;
use App\Enums\FulfillmentMethod;
use App\Enums\VendorOrderStatus;
use App\Models\DeliveryAgent;
use App\Models\VendorOrder;
use App\Services\VendorOrderService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class DispatchBoard extends Component
{
    public array $selectedAgent = [];

    public function assign(int $vendorOrderId, VendorOrderService $service): void
    {
        $agentId = $this->selectedAgent[$vendorOrderId] ?? null;

        if (! $agentId) {
            return;
        }

        $service->assignAgent(VendorOrder::findOrFail($vendorOrderId), DeliveryAgent::findOrFail($agentId));
    }

    public function render()
    {
        $packedOrders = VendorOrder::where('status', VendorOrderStatus::Packed)
            ->where('fulfillment_method', FulfillmentMethod::Delivery)
            ->with('order', 'vendor')
            ->latest()
            ->get();

        $agents = DeliveryAgent::where('availability', AgentAvailability::Available)->with('user')->get();

        return view('livewire.admin.dispatch-board', [
            'packedOrders' => $packedOrders,
            'agents' => $agents,
        ]);
    }
}
