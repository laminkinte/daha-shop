<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Services\OrderService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class OrderOverview extends Component
{
    use WithPagination;

    public string $filter = 'pending_admin_review';

    public function approve(int $orderId, OrderService $service): void
    {
        $service->adminApprove(Order::findOrFail($orderId));
    }

    public function reject(int $orderId, OrderService $service): void
    {
        $service->reject(Order::findOrFail($orderId), 'Rejected by admin');
    }

    public function render()
    {
        $query = Order::with('user', 'vendorOrders')->latest();

        if ($this->filter !== 'all') {
            $query->where('confirmation_status', $this->filter);
        }

        return view('livewire.admin.order-overview', [
            'orders' => $query->paginate(10),
        ]);
    }
}
