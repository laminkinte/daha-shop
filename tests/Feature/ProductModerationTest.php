<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\VendorStatus;
use App\Livewire\Admin\ProductApprovals;
use App\Livewire\Storefront\ProductCatalog;
use App\Livewire\Storefront\ProductDetail;
use App\Livewire\Vendor\ProductManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductModerationTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(): Vendor
    {
        return Vendor::create([
            'user_id' => User::factory()->vendor()->create()->id,
            'business_name' => 'Test Vendor',
            'slug' => 'test-vendor',
            'business_phone' => '+2348000000001',
            'business_address' => 'Address',
            'status' => VendorStatus::Approved,
        ]);
    }

    public function test_new_product_submitted_for_review_is_not_visible_on_storefront(): void
    {
        $vendor = $this->makeVendor();
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        Livewire::actingAs($vendor->user)
            ->test(ProductManager::class)
            ->call('create')
            ->set('categoryId', $category->id)
            ->set('name', 'New Phone')
            ->set('description', 'A phone')
            ->set('price', '10000')
            ->set('stock', 5)
            ->call('submitForReview');

        $product = Product::where('name', 'New Phone')->firstOrFail();
        $this->assertSame(ProductStatus::PendingReview, $product->status);

        Livewire::test(ProductCatalog::class)->assertDontSee('New Phone');
        $this->get(route('storefront.product', $product->slug))->assertNotFound();
    }

    public function test_admin_can_approve_a_pending_product_making_it_publicly_visible(): void
    {
        $vendor = $this->makeVendor();
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $admin = User::factory()->admin()->create();

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Pending Phone',
            'slug' => 'pending-phone',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => ProductStatus::PendingReview,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductApprovals::class)
            ->assertSee('Pending Phone')
            ->call('approve', $product->id);

        $product->refresh();
        $this->assertSame(ProductStatus::Published, $product->status);
        $this->assertSame($admin->id, $product->reviewed_by);
        $this->assertNotNull($product->reviewed_at);

        Livewire::test(ProductCatalog::class)->assertSee('Pending Phone');
        Livewire::test(ProductDetail::class, ['product' => $product])->assertSee('Pending Phone');
    }

    public function test_admin_rejecting_a_product_requires_a_reason_and_keeps_it_hidden(): void
    {
        $vendor = $this->makeVendor();
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $admin = User::factory()->admin()->create();

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Suspicious Phone',
            'slug' => 'suspicious-phone',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => ProductStatus::PendingReview,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductApprovals::class)
            ->call('reject', $product->id)
            ->assertHasErrors(["rejectionReason.{$product->id}" => 'required']);

        $product->refresh();
        $this->assertSame(ProductStatus::PendingReview, $product->status, 'Rejecting without a reason must not change status.');

        Livewire::actingAs($admin)
            ->test(ProductApprovals::class)
            ->set("rejectionReason.{$product->id}", 'Photo does not match the listed item')
            ->call('reject', $product->id);

        $product->refresh();
        $this->assertSame(ProductStatus::Rejected, $product->status);
        $this->assertSame('Photo does not match the listed item', $product->rejection_reason);

        $this->get(route('storefront.product', $product->slug))->assertNotFound();
    }

    public function test_vendor_can_edit_and_resubmit_a_rejected_product(): void
    {
        $vendor = $this->makeVendor();
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Rejected Phone',
            'slug' => 'rejected-phone',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => ProductStatus::Rejected,
            'rejection_reason' => 'Bad photo',
            'reviewed_at' => now(),
        ]);

        Livewire::actingAs($vendor->user)
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('editingRejectionReason', 'Bad photo')
            ->set('price', '12000')
            ->call('submitForReview');

        $product->refresh();
        $this->assertSame(ProductStatus::PendingReview, $product->status);
        $this->assertNull($product->rejection_reason);
        $this->assertNull($product->reviewed_at);
    }

    public function test_editing_a_published_product_does_not_require_re_review(): void
    {
        $vendor = $this->makeVendor();
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Live Phone',
            'slug' => 'live-phone',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => ProductStatus::Published,
        ]);

        Livewire::actingAs($vendor->user)
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('isAwaitingOrLive', true)
            ->set('stock', 2)
            ->call('saveChanges');

        $product->refresh();
        $this->assertSame(ProductStatus::Published, $product->status);
        $this->assertSame(2, $product->stock);
    }

    public function test_a_vendor_cannot_add_an_unpublished_product_to_cart_via_catalog(): void
    {
        $vendor = $this->makeVendor();
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Draft Phone',
            'slug' => 'draft-phone',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => ProductStatus::Draft,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(ProductCatalog::class)->call('addToCart', $product->id);
    }
}
