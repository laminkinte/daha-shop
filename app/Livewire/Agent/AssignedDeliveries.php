<?php

namespace App\Livewire\Agent;

use App\Enums\VendorOrderStatus;
use App\Models\VendorOrder;
use App\Services\VendorOrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class AssignedDeliveries extends Component
{
    public function markOutForDelivery(int $vendorOrderId, VendorOrderService $service): void
    {
        $vendorOrder = Auth::user()->deliveryAgent->vendorOrders()->findOrFail($vendorOrderId);
        $service->markOutForDelivery($vendorOrder);
    }

    public function render()
    {
        $agent = Auth::user()->deliveryAgent;

        $deliveries = $agent->vendorOrders()
            ->whereIn('status', [VendorOrderStatus::AssignedToAgent, VendorOrderStatus::OutForDelivery])
            ->with('order.address', 'vendor', 'items')
            ->latest()
            ->get();

        return view('livewire.agent.assigned-deliveries', ['deliveries' => $deliveries]);
    }
}
