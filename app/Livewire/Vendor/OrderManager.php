<?php

namespace App\Livewire\Vendor;

use App\Enums\DeliveryPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Models\VendorOrder;
use App\Services\DeliveryPaymentService;
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

    /** @var array<int, string> */
    public array $paymentMethod = [];

    /** @var array<int, string> */
    public array $customerPhone = [];

    public ?string $error = null;

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

    public function deliverMyself(int $vendorOrderId, VendorOrderService $service): void
    {
        $service->assignSelfDelivery($this->authorizedOrder($vendorOrderId));
    }

    public function markReadyForPickup(int $vendorOrderId, VendorOrderService $service): void
    {
        $service->markReadyForPickup($this->authorizedOrder($vendorOrderId));
    }

    /**
     * Starts (or retries) digital collection of payment - shared by both the
     * vendor-self-delivery flow (status=out_for_delivery, no agent) and the
     * pickup flow (status=ready_for_pickup), same DeliveryPaymentService the
     * agent's DeliveryDetail component uses. Array-keyed by vendor order id
     * since this component manages a list, not a single order.
     */
    public function startDigitalPayment(int $vendorOrderId, DeliveryPaymentService $service): void
    {
        $this->error = null;
        $vendorOrder = $this->authorizedOrder($vendorOrderId);
        $method = $this->paymentMethod[$vendorOrderId] ?? 'qr';

        if ($method === 'manual') {
            $this->validate([
                "customerPhone.{$vendorOrderId}" => 'required|string',
            ]);
        }

        try {
            $service->initialize($vendorOrder, $method, $this->customerPhone[$vendorOrderId] ?? null);
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'We could not start the payment right now. Please try again shortly.';
        }
    }

    /**
     * Polled every 5s while any of this vendor's orders have a pending
     * digital payment - batched into one call rather than one poll per row
     * since this is a list view, not a single-order detail page.
     */
    public function refreshPendingPayments(DeliveryPaymentService $service): void
    {
        $pending = Auth::user()->vendor->vendorOrders()
            ->whereHas('deliveryPayment', fn ($q) => $q->where('status', DeliveryPaymentStatus::Pending))
            ->with('deliveryPayment')
            ->get();

        foreach ($pending as $vendorOrder) {
            $service->verifyAndComplete($vendorOrder->deliveryPayment->reference);
        }
    }

    private function authorizedOrder(int $vendorOrderId): VendorOrder
    {
        return Auth::user()->vendor->vendorOrders()->findOrFail($vendorOrderId);
    }

    public function render()
    {
        $query = Auth::user()->vendor->vendorOrders()->with('order', 'items', 'deliveryPayment')->latest();

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return view('livewire.vendor.order-manager', [
            'vendorOrders' => $query->paginate(10),
            'statuses' => VendorOrderStatus::cases(),
        ]);
    }
}
