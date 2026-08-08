<?php

namespace Tests\Feature;

use App\Enums\AgentAvailability;
use App\Enums\ConfirmationStatus;
use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PayoutStatus;
use App\Enums\ProductStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Exports\GenericExport;
use App\Models\Address;
use App\Models\AdminActionLog;
use App\Models\BlacklistedNumber;
use App\Models\CashReconciliation;
use App\Models\Category;
use App\Models\DeliveryAgent;
use App\Models\DeliveryFee;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayout;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        $this->seed(NigeriaGeographySeeder::class);
        $state = State::first();
        $lga = $state->lgas()->first();
        $customer = User::factory()->create();

        return Order::create([
            'order_number' => 'ORD'.uniqid(),
            'user_id' => $customer->id,
            'address_id' => Address::create([
                'user_id' => $customer->id,
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'label' => 'Home',
                'area' => 'Area',
                'street_address' => 'Street',
                'phone' => '+2348000000000',
            ])->id,
            'status' => OrderStatus::Completed,
            'confirmation_status' => ConfirmationStatus::Confirmed,
            'items_subtotal' => 500000,
            'delivery_fee_total' => 0,
            'cod_amount_expected' => 500000,
            'cod_amount_collected' => 500000,
        ]);
    }

    private function makeVendor(string $businessName = 'Test Shop'): Vendor
    {
        $vendorUser = User::factory()->vendor()->create();

        return Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => $businessName,
            'slug' => \Illuminate\Support\Str::slug($businessName).'-'.uniqid(),
            'business_phone' => '+2348012340000',
            'business_address' => '1 Test Street',
            'status' => VendorStatus::Approved,
        ]);
    }

    public function test_orders_export_returns_an_excel_file_with_expected_rows(): void
    {
        Excel::fake();

        $order = $this->makeOrder();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.orders.export', ['filter' => 'all']))->assertOk();

        Excel::assertDownloaded('orders.xlsx', function (GenericExport $export) use ($order) {
            $row = $export->map($order->fresh());

            return $export->collection()->contains($order->id)
                && in_array(5000, $row, true);
        });
    }

    public function test_orders_export_is_permission_gated(): void
    {
        $scopedAdmin = User::factory()->scopedAdmin(['products'])->create();

        $this->actingAs($scopedAdmin)->get(route('admin.orders.export'))->assertForbidden();
    }

    public function test_reconciliation_export_returns_an_excel_file_with_expected_rows(): void
    {
        Excel::fake();

        $order = $this->makeOrder();
        $state = State::first();
        $lga = $state->lgas()->first();
        $vendor = $this->makeVendor();

        $agentUser = User::factory()->agent()->create();
        $agent = DeliveryAgent::create([
            'user_id' => $agentUser->id,
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'availability' => AgentAvailability::Available,
        ]);

        $vendorOrder = VendorOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'delivery_agent_id' => $agent->id,
            'status' => VendorOrderStatus::Delivered,
            'items_subtotal' => 500000,
            'delivery_fee' => 0,
            'delivered_at' => now(),
        ]);

        $reconciliation = CashReconciliation::create([
            'delivery_agent_id' => $agent->id,
            'vendor_order_id' => $vendorOrder->id,
            'amount_expected' => 500000,
            'amount_collected' => 500000,
            'status' => ReconciliationStatus::Collected,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.reconciliation.export', ['filter' => 'all']))->assertOk();

        Excel::assertDownloaded('reconciliation.xlsx', function (GenericExport $export) use ($reconciliation, $agentUser, $order) {
            $row = $export->map($reconciliation->fresh());

            return in_array($agentUser->name, $row, true) && in_array($order->order_number, $row, true);
        });
    }

    public function test_payouts_export_returns_an_excel_file_with_expected_rows(): void
    {
        Excel::fake();

        $vendor = $this->makeVendor('Payout Shop');

        $payout = VendorPayout::create([
            'vendor_id' => $vendor->id,
            'period_start' => now()->subDays(30),
            'period_end' => now(),
            'total_amount' => 500000,
            'status' => PayoutStatus::Paid,
            'reference' => 'PAYOUT-REF-1',
            'paid_at' => now(),
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.payouts.export', ['filter' => 'all']))->assertOk();

        Excel::assertDownloaded('payouts.xlsx', function (GenericExport $export) use ($payout) {
            $row = $export->map($payout->fresh());

            return in_array('Payout Shop', $row, true) && in_array('PAYOUT-REF-1', $row, true);
        });
    }

    public function test_vendors_export_returns_an_excel_file_and_is_permission_gated(): void
    {
        Excel::fake();

        $vendor = $this->makeVendor('Exportable Vendor');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.vendors.export', ['filter' => 'all']))->assertOk();

        Excel::assertDownloaded('vendors.xlsx', function (GenericExport $export) use ($vendor) {
            $row = $export->map($vendor->fresh()->load('user', 'state', 'lga'));

            return in_array('Exportable Vendor', $row, true);
        });

        $scopedAdmin = User::factory()->scopedAdmin(['products'])->create();
        $this->actingAs($scopedAdmin)->get(route('admin.vendors.export'))->assertForbidden();
    }

    public function test_products_export_returns_an_excel_file_and_is_permission_gated(): void
    {
        Excel::fake();

        $vendor = $this->makeVendor();
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics-'.uniqid()]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Exportable Product',
            'slug' => 'exportable-product-'.uniqid(),
            'base_price' => 250000,
            'stock' => 10,
            'status' => ProductStatus::Published,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.products.export', ['filter' => 'all']))->assertOk();

        Excel::assertDownloaded('products.xlsx', function (GenericExport $export) use ($product) {
            $row = $export->map($product->fresh()->load('vendor', 'category'));

            return in_array('Exportable Product', $row, true) && in_array(2500, $row, true);
        });

        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();
        $this->actingAs($scopedAdmin)->get(route('admin.products.export'))->assertForbidden();
    }

    public function test_dispatch_export_returns_an_excel_file_and_is_permission_gated(): void
    {
        Excel::fake();

        $order = $this->makeOrder();
        $vendor = $this->makeVendor('Dispatch Vendor');

        $vendorOrder = VendorOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'status' => VendorOrderStatus::Packed,
            'fulfillment_method' => FulfillmentMethod::Delivery,
            'items_subtotal' => 500000,
            'delivery_fee' => 0,
            'packed_at' => now(),
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dispatch.export'))->assertOk();

        Excel::assertDownloaded('dispatch.xlsx', function (GenericExport $export) use ($vendorOrder) {
            $row = $export->map($vendorOrder->fresh()->load('order', 'vendor'));

            return in_array('Dispatch Vendor', $row, true);
        });

        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();
        $this->actingAs($scopedAdmin)->get(route('admin.dispatch.export'))->assertForbidden();
    }

    public function test_agents_export_returns_an_excel_file_and_is_permission_gated(): void
    {
        Excel::fake();

        $this->seed(NigeriaGeographySeeder::class);
        $state = State::first();
        $lga = $state->lgas()->first();

        $agentUser = User::factory()->agent()->create();
        $agent = DeliveryAgent::create([
            'user_id' => $agentUser->id,
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'vehicle_type' => 'motorcycle',
            'availability' => AgentAvailability::Available,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.agents.export'))->assertOk();

        Excel::assertDownloaded('agents.xlsx', function (GenericExport $export) use ($agentUser) {
            $row = $export->map($export->collection()->firstWhere('user_id', $agentUser->id)->load('user', 'state', 'lga'));

            return in_array($agentUser->name, $row, true);
        });

        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();
        $this->actingAs($scopedAdmin)->get(route('admin.agents.export'))->assertForbidden();
    }

    public function test_delivery_zones_export_returns_an_excel_file_and_is_permission_gated(): void
    {
        Excel::fake();

        $this->seed(NigeriaGeographySeeder::class);
        $state = State::first();
        $lga = $state->lgas()->first();

        $zone = DeliveryZone::create([
            'name' => $lga->name.' Zone',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
        ]);

        DeliveryFee::create([
            'delivery_zone_id' => $zone->id,
            'vendor_id' => null,
            'fee' => 150000,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.delivery-zones.export'))->assertOk();

        Excel::assertDownloaded('delivery-zones.xlsx', function (GenericExport $export) use ($zone) {
            $row = $export->map($zone->fresh()->load('state', 'lga', 'fees'));

            return in_array($zone->name, $row, true) && in_array(1500, $row, true);
        });

        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();
        $this->actingAs($scopedAdmin)->get(route('admin.delivery-zones.export'))->assertForbidden();
    }

    public function test_blacklist_export_returns_an_excel_file_and_is_permission_gated(): void
    {
        Excel::fake();

        $entry = BlacklistedNumber::create([
            'phone' => '+2348011112222',
            'email' => null,
            'reason' => 'Chargeback fraud',
            'blocked_at' => now(),
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.blacklist.export'))->assertOk();

        Excel::assertDownloaded('blacklist.xlsx', function (GenericExport $export) use ($entry) {
            $row = $export->map($entry->fresh());

            return in_array('+2348011112222', $row, true) && in_array('Chargeback fraud', $row, true);
        });

        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();
        $this->actingAs($scopedAdmin)->get(route('admin.blacklist.export'))->assertForbidden();
    }

    public function test_admins_export_is_super_admin_only(): void
    {
        Excel::fake();

        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin)->get(route('admin.admins.export'))->assertOk();

        Excel::assertDownloaded('admins.xlsx', function (GenericExport $export) use ($superAdmin) {
            $row = $export->map($superAdmin->fresh());

            return in_array('Super Admin', $row, true);
        });

        $this->actingAs($scopedAdmin)->get(route('admin.admins.export'))->assertForbidden();
    }

    public function test_admin_audit_log_export_is_super_admin_only(): void
    {
        Excel::fake();

        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $log = AdminActionLog::create([
            'actor_id' => $superAdmin->id,
            'actor_name' => $superAdmin->name,
            'actor_email' => $superAdmin->email,
            'target_id' => $scopedAdmin->id,
            'target_name' => $scopedAdmin->name,
            'target_email' => $scopedAdmin->email,
            'action' => 'created',
            'changes' => ['permissions' => ['vendors']],
        ]);

        $this->actingAs($superAdmin)->get(route('admin.admins.audit-log.export'))->assertOk();

        Excel::assertDownloaded('admin-audit-log.xlsx', function (GenericExport $export) use ($log) {
            $row = $export->map($log->fresh());

            return in_array($log->actor_name, $row, true);
        });

        $this->actingAs($scopedAdmin)->get(route('admin.admins.audit-log.export'))->assertForbidden();
    }
}
