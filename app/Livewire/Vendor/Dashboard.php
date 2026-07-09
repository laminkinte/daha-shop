<?php

namespace App\Livewire\Vendor;

use App\Enums\VendorOrderStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $vendor = Auth::user()->vendor;

        $stats = [
            'products' => $vendor->products()->count(),
            'pending_orders' => $vendor->vendorOrders()->whereIn('status', [VendorOrderStatus::Pending, VendorOrderStatus::Accepted])->count(),
            'delivered_this_month' => $vendor->vendorOrders()->where('status', VendorOrderStatus::Delivered)->whereMonth('delivered_at', now()->month)->count(),
            'pending_payout' => $vendor->vendorOrders()->where('status', VendorOrderStatus::Delivered)->whereNull('vendor_payout_id')->sum('items_subtotal'),
        ];

        $recentOrders = $vendor->vendorOrders()->with('order')->latest()->limit(8)->get();

        return view('livewire.vendor.dashboard', [
            'vendor' => $vendor,
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }
}
