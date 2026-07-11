<?php

namespace Tests\Feature;

use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SellerVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Livewire's file-upload testing helpers expect a fake Testing\File
     * (which carries the ->name property they read internally), so we
     * create one the normal way and then swap in real image bytes -
     * this keeps Livewire's test plumbing happy while still giving the
     * clarity checker genuine pixel data to analyze.
     */
    private function sharpFixture(string $fieldName): UploadedFile
    {
        $fake = UploadedFile::fake()->image($fieldName.'.jpg');
        file_put_contents($fake->getPathname(), file_get_contents(database_path('seeders/images/products/samsung-galaxy.jpg')));

        return $fake;
    }

    private function blurryFixture(string $fieldName): UploadedFile
    {
        $image = imagecreatefromjpeg(database_path('seeders/images/products/samsung-galaxy.jpg'));

        for ($i = 0; $i < 15; $i++) {
            imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        }

        $fake = UploadedFile::fake()->image($fieldName.'.jpg');
        imagejpeg($image, $fake->getPathname(), 90);
        imagedestroy($image);

        return $fake;
    }

    public function test_seller_registration_requires_id_document_and_selfie(): void
    {
        Volt::test('pages.auth.register')
            ->set('accountType', 'seller')
            ->set('name', 'Test Seller')
            ->set('email', 'seller@example.com')
            ->set('phone', '+2348012345678')
            ->set('business_name', 'Test Business')
            ->set('business_phone', '+2348012345678')
            ->set('business_address', '1 Test Street')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasErrors(['idDocument', 'selfie']);

        $this->assertDatabaseCount('vendors', 0);
    }

    public function test_seller_registration_succeeds_with_clear_photos_and_stores_them_privately(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        Volt::test('pages.auth.register')
            ->set('accountType', 'seller')
            ->set('name', 'Test Seller')
            ->set('email', 'seller@example.com')
            ->set('phone', '+2348012345678')
            ->set('business_name', 'Test Business')
            ->set('business_phone', '+2348012345678')
            ->set('business_address', '1 Test Street')
            ->set('idDocumentType', 'national_id')
            ->set('idDocument', $this->sharpFixture('id'))
            ->set('selfie', $this->sharpFixture('selfie'))
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect();

        $vendor = Vendor::firstOrFail();

        $this->assertSame(VendorStatus::Pending, $vendor->status);
        $this->assertSame('national_id', $vendor->id_document_type);
        $this->assertNotNull($vendor->id_document_path);
        $this->assertNotNull($vendor->selfie_path);

        // Must be on the private disk, not reachable via the public /storage/ URL.
        Storage::disk('local')->assertExists($vendor->id_document_path);
        Storage::disk('local')->assertExists($vendor->selfie_path);
        Storage::disk('public')->assertMissing($vendor->id_document_path);
        Storage::disk('public')->assertMissing($vendor->selfie_path);
    }

    public function test_blurry_id_document_blocks_registration_with_a_clear_warning(): void
    {
        Volt::test('pages.auth.register')
            ->set('accountType', 'seller')
            ->set('name', 'Test Seller')
            ->set('email', 'seller@example.com')
            ->set('phone', '+2348012345678')
            ->set('business_name', 'Test Business')
            ->set('business_phone', '+2348012345678')
            ->set('business_address', '1 Test Street')
            ->set('idDocument', $this->blurryFixture('id'))
            ->set('selfie', $this->sharpFixture('selfie'))
            ->assertSet('idDocumentClarityWarning', fn ($warning) => ! empty($warning))
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasErrors('idDocument');

        $this->assertDatabaseCount('vendors', 0);
    }

    public function test_admin_can_view_vendor_documents_and_invalid_type_is_404(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $vendor = $this->createVendorWithDocuments();

        $this->actingAs($admin)
            ->get(route('admin.vendors.document', [$vendor, 'id']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.vendors.document', [$vendor, 'selfie']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.vendors.document', [$vendor, 'not-a-real-type']))
            ->assertNotFound();
    }

    public function test_non_admins_cannot_view_vendor_documents(): void
    {
        Storage::fake('local');

        $customer = User::factory()->create();
        $vendor = $this->createVendorWithDocuments();

        $this->actingAs($customer)
            ->get(route('admin.vendors.document', [$vendor, 'id']))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login_when_viewing_vendor_documents(): void
    {
        Storage::fake('local');

        $vendor = $this->createVendorWithDocuments();

        $this->get(route('admin.vendors.document', [$vendor, 'id']))
            ->assertRedirect(route('login'));
    }

    private function createVendorWithDocuments(): Vendor
    {
        return Vendor::create([
            'user_id' => User::factory()->vendor()->create()->id,
            'business_name' => 'Doc Test Vendor',
            'slug' => 'doc-test-vendor-'.uniqid(),
            'business_phone' => '+2348000000009',
            'business_address' => 'Address',
            'status' => VendorStatus::Pending,
            'id_document_type' => 'passport',
            'id_document_path' => $this->sharpFixture('id')->store('vendor-kyc', 'local'),
            'selfie_path' => $this->sharpFixture('selfie')->store('vendor-kyc', 'local'),
        ]);
    }
}
