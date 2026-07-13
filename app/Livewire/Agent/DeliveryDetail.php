<?php

namespace App\Livewire\Agent;

use App\Enums\DeliveryFailureReason;
use App\Models\VendorOrder;
use App\Services\VendorOrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.dashboard')]
class DeliveryDetail extends Component
{
    use WithFileUploads;

    public VendorOrder $vendorOrder;

    public string $cashCollected = '';

    public string $denominationNotes = '';

    public $proofPhoto;

    public string $failureReason = 'customer_unreachable';

    public function mount(int $vendorOrderId): void
    {
        $this->vendorOrder = Auth::user()->deliveryAgent->vendorOrders()->with('order.address', 'vendor', 'items')->findOrFail($vendorOrderId);
        $this->cashCollected = number_format($this->vendorOrder->cashDueAtDelivery() / 100, 2, '.', '');
    }

    public function markDelivered(VendorOrderService $service)
    {
        $this->validate([
            'cashCollected' => 'required|numeric|min:0',
            'proofPhoto' => 'nullable|image|max:4096',
        ]);

        $path = $this->proofPhoto?->store('proof-of-delivery', 'public');

        $service->markDelivered(
            $this->vendorOrder,
            (int) round((float) $this->cashCollected * 100),
            $path,
            $this->denominationNotes ?: null,
        );

        return $this->redirect(route('agent.deliveries'), navigate: true);
    }

    public function markFailed(VendorOrderService $service)
    {
        $service->markFailed($this->vendorOrder, DeliveryFailureReason::from($this->failureReason));

        return $this->redirect(route('agent.deliveries'), navigate: true);
    }

    public function render()
    {
        return view('livewire.agent.delivery-detail', [
            'failureReasons' => DeliveryFailureReason::cases(),
        ]);
    }
}
