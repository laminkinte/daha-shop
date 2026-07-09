<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Models\Order;
use App\Models\VendorOrder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $totalOrders = Order::count();
        $rejected = Order::whereIn('status', [OrderStatus::Rejected, OrderStatus::Cancelled])->count();

        $totalVendorOrders = VendorOrder::whereIn('status', [VendorOrderStatus::Delivered, VendorOrderStatus::Failed])->count();
        $delivered = VendorOrder::where('status', VendorOrderStatus::Delivered)->count();

        $stats = [
            'gmv' => Order::where('status', OrderStatus::Completed)->sum('cod_amount_collected'),
            'pending_vendor_approvals' => \App\Models\Vendor::where('status', VendorStatus::Pending)->count(),
            'pending_admin_review' => Order::where('confirmation_status', \App\Enums\ConfirmationStatus::PendingAdminReview)->count(),
            'order_rejection_rate' => $totalOrders > 0 ? round(($rejected / $totalOrders) * 100, 1) : 0,
            'delivery_success_rate' => $totalVendorOrders > 0 ? round(($delivered / $totalVendorOrders) * 100, 1) : 0,
        ];

        return view('livewire.admin.dashboard', ['stats' => $stats]);
    }
}
