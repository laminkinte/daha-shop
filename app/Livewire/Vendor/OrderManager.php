<?php

namespace App\Livewire\Vendor;

use App\Enums\VendorOrderStatus;
use App\Models\VendorOrder;
use App\Services\VendorOrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class OrderManager extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public array $pickupCash = [];

    public function accept(int $vendorOrderId, VendorOrderService $service): void
    {
        $service->accept($this->authorizedOrder($vendorOrderId));
    }

    public function reject(int $vendorOrderId, VendorOrderService $service): void
    {
        $service->reject($this->authorizedOrder($vendorOrderId), 'Rejected by vendor');
    }

    public function pack(int $vendorOrderId, VendorOrderService $service): void
    {
        $service->pack($this->authorizedOrder($vendorOrderId));
    }

    public function markReadyForPickup(int $vendorOrderId, VendorOrderService $service): void
    {
        $service->markReadyForPickup($this->authorizedOrder($vendorOrderId));
    }

    public function confirmPickedUp(int $vendorOrderId, VendorOrderService $service): void
    {
        $vendorOrder = $this->authorizedOrder($vendorOrderId);
        $cash = (int) round(((float) ($this->pickupCash[$vendorOrderId] ?? 0)) * 100);

        $service->markPickedUp($vendorOrder, $cash > 0 ? $cash : $vendorOrder->codTotal());
    }

    private function authorizedOrder(int $vendorOrderId): VendorOrder
    {
        return Auth::user()->vendor->vendorOrders()->findOrFail($vendorOrderId);
    }

    public function render()
    {
        $query = Auth::user()->vendor->vendorOrders()->with('order', 'items')->latest();

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return view('livewire.vendor.order-manager', [
            'vendorOrders' => $query->paginate(10),
            'statuses' => VendorOrderStatus::cases(),
        ]);
    }
}
