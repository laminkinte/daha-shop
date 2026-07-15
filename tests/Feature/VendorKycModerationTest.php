<?php

namespace Tests\Feature;

use App\Enums\VendorStatus;
use App\Livewire\Admin\VendorApprovals;
use App\Livewire\Vendor\IdentityVerification;
use App\Mail\VendorDocumentRetakeRequested;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class VendorKycModerationTest extends TestCase
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
            'status' => VendorStatus::Pending,
            'id_document_type' => 'national_id',
            'id_document_path' => 'vendor-kyc/old-id.jpg',
            'selfie_path' => 'vendor-kyc/old-selfie.jpg',
        ]);
    }

    public function test_admin_requesting_an_id_document_retake_requires_a_reason(): void
    {
        $vendor = $this->makeVendor();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(VendorApprovals::class)
            ->call('requestRetake', $vendor->id, 'id')
            ->assertHasErrors(["rejectionReason.{$vendor->id}.id" => 'required']);

        $vendor->refresh();
        $this->assertNull($vendor->id_document_rejection_reason);
    }

    public function test_admin_can_request_an_id_document_retake_without_suspending_the_vendor(): void
    {
        Queue::fake();
        Mail::fake();

        $vendor = $this->makeVendor();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(VendorApprovals::class)
            ->set("rejectionReason.{$vendor->id}.id", 'Photo is too blurry to read')
            ->call('requestRetake', $vendor->id, 'id');

        $vendor->refresh();
        $this->assertSame('Photo is too blurry to read', $vendor->id_document_rejection_reason);
        $this->assertSame($admin->id, $vendor->reviewed_by);
        $this->assertNotNull($vendor->reviewed_at);
        $this->assertSame(VendorStatus::Pending, $vendor->status, 'Requesting a retake must not suspend the vendor.');

        Queue::assertPushed(\App\Jobs\SendOrderStatusSms::class, function ($job) use ($vendor) {
            return $job->phone === $vendor->business_phone
                && str_contains($job->message, 'ID document')
                && str_contains($job->message, 'Photo is too blurry to read');
        });

        Mail::assertQueued(VendorDocumentRetakeRequested::class, function ($mail) use ($vendor) {
            return $mail->hasTo($vendor->user->email)
                && $mail->documentLabel === 'ID document'
                && $mail->reason === 'Photo is too blurry to read';
        });
    }

    public function test_a_phone_pin_vendor_does_not_get_a_retake_email_only_sms(): void
    {
        Queue::fake();
        Mail::fake();

        $phoneUser = User::factory()->vendor()->create([
            'uses_pin' => true,
            'email' => 'responsive-check-shop@phone.dahashop.internal',
        ]);
        $vendor = Vendor::create([
            'user_id' => $phoneUser->id,
            'business_name' => 'Phone Vendor',
            'slug' => 'phone-vendor',
            'business_phone' => '+2348000000099',
            'business_address' => 'Address',
            'status' => VendorStatus::Pending,
            'id_document_path' => 'vendor-kyc/old-id.jpg',
        ]);
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(VendorApprovals::class)
            ->set("rejectionReason.{$vendor->id}.id", 'Blurry')
            ->call('requestRetake', $vendor->id, 'id');

        Queue::assertPushed(\App\Jobs\SendOrderStatusSms::class);
        Mail::assertNothingQueued();
    }

    public function test_admin_can_request_a_selfie_retake_independently_of_the_id_document(): void
    {
        Queue::fake();

        $vendor = $this->makeVendor();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(VendorApprovals::class)
            ->set("rejectionReason.{$vendor->id}.selfie", "Doesn't match the ID photo")
            ->call('requestRetake', $vendor->id, 'selfie');

        $vendor->refresh();
        $this->assertSame("Doesn't match the ID photo", $vendor->selfie_rejection_reason);
        $this->assertNull($vendor->id_document_rejection_reason);
    }

    public function test_vendor_can_resubmit_a_rejected_id_document_which_clears_the_reason(): void
    {
        $vendor = $this->makeVendor();
        $vendor->update([
            'id_document_rejection_reason' => 'Blurry photo',
            'reviewed_at' => now(),
        ]);

        $newDocument = UploadedFile::fake()->image('id.jpg', 800, 600);

        Livewire::actingAs($vendor->user)
            ->test(IdentityVerification::class)
            ->assertSee('Blurry photo')
            ->set('idDocumentType', 'passport')
            ->set('idDocument', $newDocument)
            ->call('resubmitIdDocument');

        $vendor->refresh();
        $this->assertNull($vendor->id_document_rejection_reason);
        $this->assertSame('passport', $vendor->id_document_type);
        $this->assertNotSame('vendor-kyc/old-id.jpg', $vendor->id_document_path);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists($vendor->id_document_path));
    }

    public function test_vendor_can_resubmit_a_rejected_selfie_which_clears_the_reason(): void
    {
        $vendor = $this->makeVendor();
        $vendor->update(['selfie_rejection_reason' => "Doesn't match ID"]);

        $newSelfie = UploadedFile::fake()->image('selfie.jpg', 800, 600);

        Livewire::actingAs($vendor->user)
            ->test(IdentityVerification::class)
            ->assertSee("Doesn't match ID")
            ->set('selfie', $newSelfie)
            ->call('resubmitSelfie');

        $vendor->refresh();
        $this->assertNull($vendor->selfie_rejection_reason);
        $this->assertNotSame('vendor-kyc/old-selfie.jpg', $vendor->selfie_path);
    }

    public function test_approving_a_vendor_clears_any_lingering_rejection_reasons(): void
    {
        $vendor = $this->makeVendor();
        $vendor->update(['id_document_rejection_reason' => 'Stale reason that was never resolved']);
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(VendorApprovals::class)
            ->call('approve', $vendor->id);

        $vendor->refresh();
        $this->assertSame(VendorStatus::Approved, $vendor->status);
        $this->assertNull($vendor->id_document_rejection_reason);
    }

    public function test_a_customer_cannot_access_the_vendor_identity_verification_page(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('vendor.identity'))->assertForbidden();
    }

    public function test_the_vendor_identity_verification_page_and_admin_vendor_approvals_page_load_over_http(): void
    {
        $vendor = $this->makeVendor();
        $admin = User::factory()->admin()->create();

        $this->actingAs($vendor->user)->get(route('vendor.identity'))->assertOk();
        $this->actingAs($admin)->get(route('admin.vendors'))->assertOk()->assertSee('Test Vendor');
    }
}
