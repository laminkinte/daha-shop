<?php

namespace App\Livewire\Admin;

use App\Enums\ConfirmationStatus;
use App\Enums\OrderStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class Dashboard extends Component
{
    public string $range = '30d';

    public function setRange(string $range): void
    {
        $this->range = $range;
    }

    private function periodStart(): ?Carbon
    {
        return match ($this->range) {
            '7d' => now()->subDays(7)->startOfDay(),
            '30d' => now()->subDays(30)->startOfDay(),
            '90d' => now()->subDays(90)->startOfDay(),
            default => null, // 'all'
        };
    }

    public function render()
    {
        $from = $this->periodStart();

        $orders = Order::query()->when($from, fn ($q) => $q->where('created_at', '>=', $from));
        $totalOrders = (clone $orders)->count();
        $rejected = (clone $orders)->whereIn('status', [OrderStatus::Rejected, OrderStatus::Cancelled])->count();

        $vendorOrders = VendorOrder::query()->when($from, fn ($q) => $q->where('created_at', '>=', $from));
        $totalVendorOrders = (clone $vendorOrders)->whereIn('status', [VendorOrderStatus::Delivered, VendorOrderStatus::Failed])->count();
        $delivered = (clone $vendorOrders)->where('status', VendorOrderStatus::Delivered)->count();

        $stats = [
            'gmv' => (clone $orders)->where('status', OrderStatus::Completed)->sum('cod_amount_collected'),
            'pending_vendor_approvals' => Vendor::where('status', VendorStatus::Pending)->count(),
            'pending_admin_review' => Order::where('confirmation_status', ConfirmationStatus::PendingAdminReview)->count(),
            'order_rejection_rate' => $totalOrders > 0 ? round(($rejected / $totalOrders) * 100, 1) : 0,
            'delivery_success_rate' => $totalVendorOrders > 0 ? round(($delivered / $totalVendorOrders) * 100, 1) : 0,
        ];

        $gmvTrendQuery = Order::where('status', OrderStatus::Completed)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->selectRaw('DATE(created_at) as day, SUM(cod_amount_collected) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $gmvTrend = [
            'labels' => $gmvTrendQuery->pluck('day')->all(),
            'data' => $gmvTrendQuery->pluck('total')->map(fn ($kobo) => round($kobo / 100, 2))->all(),
        ];

        $topVendors = Vendor::query()
            ->withSum(['vendorOrders as revenue' => function ($query) use ($from) {
                $query->where('status', VendorOrderStatus::Delivered)
                    ->when($from, fn ($q) => $q->where('delivered_at', '>=', $from));
            }], 'items_subtotal')
            ->orderByDesc('revenue')
            ->take(5)
            ->get();

        return view('livewire.admin.dashboard', [
            'stats' => $stats,
            'gmvTrend' => $gmvTrend,
            'topVendors' => $topVendors,
        ]);
    }
}
