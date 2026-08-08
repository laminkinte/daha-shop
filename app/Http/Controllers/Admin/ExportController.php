<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentMethod;
use App\Enums\VendorOrderStatus;
use App\Exports\GenericExport;
use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\BlacklistedNumber;
use App\Models\CashReconciliation;
use App\Models\DeliveryAgent;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayout;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function orders(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = Order::with('user', 'address')->latest();

        if ($filter !== 'all') {
            $query->where('confirmation_status', $filter);
        }

        return Excel::download(new GenericExport(
            $query->get(),
            ['Order Number', 'Customer Name', 'Customer Phone', 'Status', 'Confirmation Status', 'Items Subtotal (NGN)', 'Delivery Fee (NGN)', 'COD Expected (NGN)', 'COD Collected (NGN)', 'Created At', 'Confirmed At'],
            fn (Order $order) => [
                $order->order_number,
                $order->user->name,
                $order->address->phone,
                $order->status->value,
                $order->confirmation_status->value,
                $order->items_subtotal / 100,
                $order->delivery_fee_total / 100,
                $order->cod_amount_expected / 100,
                $order->cod_amount_collected / 100,
                $order->created_at->format('Y-m-d H:i:s'),
                $order->confirmed_at?->format('Y-m-d H:i:s') ?? '',
            ],
        ), 'orders.xlsx');
    }

    public function reconciliation(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = CashReconciliation::with('deliveryAgent.user', 'vendorOrder.order')->latest();

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        return Excel::download(new GenericExport(
            $query->get(),
            ['Agent', 'Order Number', 'Amount Expected (NGN)', 'Amount Collected (NGN)', 'Status', 'Remitted Amount (NGN)', 'Remitted At'],
            fn (CashReconciliation $recon) => [
                $recon->deliveryAgent->user->name,
                $recon->vendorOrder->order->order_number,
                $recon->amount_expected / 100,
                $recon->amount_collected / 100,
                $recon->status->value,
                ($recon->remitted_amount ?? 0) / 100,
                $recon->remitted_at?->format('Y-m-d H:i:s') ?? '',
            ],
        ), 'reconciliation.xlsx');
    }

    public function payouts(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = VendorPayout::with('vendor')->latest();

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        return Excel::download(new GenericExport(
            $query->get(),
            ['Vendor', 'Period Start', 'Period End', 'Total Amount (NGN)', 'Status', 'Reference', 'Paid At'],
            fn (VendorPayout $payout) => [
                $payout->vendor->business_name,
                $payout->period_start->toDateString(),
                $payout->period_end->toDateString(),
                $payout->total_amount / 100,
                $payout->status->value,
                $payout->reference ?? '',
                $payout->paid_at?->format('Y-m-d H:i:s') ?? '',
            ],
        ), 'payouts.xlsx');
    }

    public function vendors(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = Vendor::with('user', 'state', 'lga')->latest();

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        return Excel::download(new GenericExport(
            $query->get(),
            ['Business Name', 'Owner Name', 'Email', 'Phone', 'Business Phone', 'Business Address', 'State', 'LGA', 'Status', 'Trial Ends At', 'Approved At', 'Created At'],
            fn (Vendor $vendor) => [
                $vendor->business_name,
                $vendor->user->name,
                $vendor->user->email,
                $vendor->user->phone,
                $vendor->business_phone,
                $vendor->business_address,
                $vendor->state?->name ?? '',
                $vendor->lga?->name ?? '',
                $vendor->status->value,
                $vendor->trial_ends_at?->format('Y-m-d H:i:s') ?? '',
                $vendor->approved_at?->format('Y-m-d H:i:s') ?? '',
                $vendor->created_at->format('Y-m-d H:i:s'),
            ],
        ), 'vendors.xlsx');
    }

    public function products(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = Product::with('vendor', 'category')->latest();

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        return Excel::download(new GenericExport(
            $query->get(),
            ['Product Name', 'Vendor', 'Category', 'Price (NGN)', 'Stock', 'Status', 'Rejection Reason', 'Created At'],
            fn (Product $product) => [
                $product->name,
                $product->vendor->business_name,
                $product->category->name,
                $product->base_price / 100,
                $product->stock,
                $product->status->value,
                $product->rejection_reason ?? '',
                $product->created_at->format('Y-m-d H:i:s'),
            ],
        ), 'products.xlsx');
    }

    public function dispatch()
    {
        $vendorOrders = VendorOrder::where('status', VendorOrderStatus::Packed)
            ->where('fulfillment_method', FulfillmentMethod::Delivery)
            ->with('order', 'vendor')
            ->latest()
            ->get();

        return Excel::download(new GenericExport(
            $vendorOrders,
            ['Order Number', 'Vendor', 'Amount (NGN)', 'Packed At'],
            fn (VendorOrder $vendorOrder) => [
                $vendorOrder->order->order_number,
                $vendorOrder->vendor->business_name,
                $vendorOrder->codTotal() / 100,
                $vendorOrder->packed_at?->format('Y-m-d H:i:s') ?? '',
            ],
        ), 'dispatch.xlsx');
    }

    public function agents()
    {
        $agents = DeliveryAgent::with('user', 'state', 'lga')
            ->withCount(['vendorOrders as delivered_count' => fn ($query) => $query->where('status', VendorOrderStatus::Delivered)])
            ->get();

        return Excel::download(new GenericExport(
            $agents,
            ['Name', 'Email', 'Phone', 'State', 'LGA', 'Vehicle Type', 'Availability', 'Delivered Count'],
            fn (DeliveryAgent $agent) => [
                $agent->user->name,
                $agent->user->email,
                $agent->user->phone,
                $agent->state?->name ?? '',
                $agent->lga?->name ?? '',
                $agent->vehicle_type,
                $agent->availability->value,
                $agent->delivered_count,
            ],
        ), 'agents.xlsx');
    }

    public function deliveryZones()
    {
        $zones = DeliveryZone::with('state', 'lga', 'fees')->orderBy('state_id')->get();

        return Excel::download(new GenericExport(
            $zones,
            ['Zone Name', 'State', 'LGA', 'Base Fee (NGN)'],
            fn (DeliveryZone $zone) => [
                $zone->name,
                $zone->state?->name ?? '',
                $zone->lga?->name ?? '',
                ($zone->fees->firstWhere('vendor_id', null)?->fee ?? 0) / 100,
            ],
        ), 'delivery-zones.xlsx');
    }

    public function blacklist()
    {
        $entries = BlacklistedNumber::latest()->get();

        return Excel::download(new GenericExport(
            $entries,
            ['Phone', 'Email', 'Reason', 'Blocked At'],
            fn (BlacklistedNumber $entry) => [
                $entry->phone ?? '',
                $entry->email ?? '',
                $entry->reason ?? '',
                $entry->blocked_at?->format('Y-m-d H:i:s') ?? '',
            ],
        ), 'blacklist.xlsx');
    }

    public function admins()
    {
        $admins = User::whereIn('role', ['admin', 'super_admin'])->orderBy('name')->get();

        return Excel::download(new GenericExport(
            $admins,
            ['Name', 'Email', 'Phone', 'Role', 'Permissions', 'Created At'],
            fn (User $admin) => [
                $admin->name,
                $admin->email,
                $admin->phone,
                $admin->isSuperAdmin() ? 'Super Admin' : 'Scoped Admin',
                implode(', ', $admin->admin_permissions ?? []),
                $admin->created_at->format('Y-m-d H:i:s'),
            ],
        ), 'admins.xlsx');
    }

    public function adminAuditLog()
    {
        $log = AdminActionLog::with('actor', 'target')->latest()->get();

        return Excel::download(new GenericExport(
            $log,
            ['When', 'Actor', 'Actor Email', 'Target', 'Target Email', 'Action', 'Summary'],
            fn (AdminActionLog $entry) => [
                $entry->created_at->format('Y-m-d H:i:s'),
                $entry->actor_name,
                $entry->actor_email,
                $entry->target_name,
                $entry->target_email,
                $entry->action,
                $entry->summary(),
            ],
        ), 'admin-audit-log.xlsx');
    }
}
