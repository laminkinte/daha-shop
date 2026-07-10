<?php

namespace Tests\Feature;

use App\Enums\VendorStatus;
use App\Livewire\Storefront\VendorShop;
use App\Livewire\Vendor\QrCode;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_vendor_shop_page_shows_only_that_vendors_products(): void
    {
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $vendorA = Vendor::create([
            'user_id' => User::factory()->vendor()->create()->id,
            'business_name' => 'Vendor A',
            'slug' => 'vendor-a',
            'business_phone' => '+2348000000001',
            'business_address' => 'Address A',
            'status' => VendorStatus::Approved,
        ]);

        $vendorB = Vendor::create([
            'user_id' => User::factory()->vendor()->create()->id,
            'business_name' => 'Vendor B',
            'slug' => 'vendor-b',
            'business_phone' => '+2348000000002',
            'business_address' => 'Address B',
            'status' => VendorStatus::Approved,
        ]);

        Product::create([
            'vendor_id' => $vendorA->id,
            'category_id' => $category->id,
            'name' => 'Vendor A Product',
            'slug' => 'vendor-a-product',
            'base_price' => 100000,
            'stock' => 5,
            'status' => 'published',
        ]);

        Product::create([
            'vendor_id' => $vendorB->id,
            'category_id' => $category->id,
            'name' => 'Vendor B Product',
            'slug' => 'vendor-b-product',
            'base_price' => 200000,
            'stock' => 5,
            'status' => 'published',
        ]);

        Livewire::test(VendorShop::class, ['vendor' => $vendorA])
            ->assertSee('Vendor A Product')
            ->assertDontSee('Vendor B Product');
    }

    public function test_unapproved_vendor_shop_is_not_publicly_visible(): void
    {
        $vendor = Vendor::create([
            'user_id' => User::factory()->vendor()->create()->id,
            'business_name' => 'Pending Vendor',
            'slug' => 'pending-vendor',
            'business_phone' => '+2348000000003',
            'business_address' => 'Address C',
            'status' => VendorStatus::Pending,
        ]);

        $this->get(route('storefront.vendor', $vendor->slug))->assertNotFound();
    }

    public function test_vendor_can_generate_a_qr_code_linking_to_their_own_shop(): void
    {
        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'QR Test Vendor',
            'slug' => 'qr-test-vendor',
            'business_phone' => '+2348000000004',
            'business_address' => 'Address D',
            'status' => VendorStatus::Approved,
        ]);

        Livewire::actingAs($vendorUser)
            ->test(QrCode::class)
            ->assertSee(route('storefront.vendor', $vendor->slug))
            ->assertSee('data:image/png;base64');
    }

    public function test_non_vendors_cannot_access_the_qr_code_page(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('vendor.qr-code'))->assertForbidden();
    }
}
