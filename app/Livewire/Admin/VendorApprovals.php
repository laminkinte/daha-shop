<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Jobs\SendOrderStatusSms;
use App\Mail\VendorAccountCreatedMail;
use App\Mail\VendorDocumentRetakeRequested;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class VendorApprovals extends Component
{
    use WithPagination;

    public string $filter = 'pending';

    public array $rejectionReason = [];

    public bool $showForm = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $businessName = '';

    public string $businessPhone = '';

    public string $businessAddress = '';

    public ?int $stateId = null;

    public ?int $lgaId = null;

    public function getLgasProperty()
    {
        return $this->stateId ? State::find($this->stateId)?->lgas()->orderBy('name')->get() : collect();
    }

    public function create(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'businessName' => 'required|string|max:255',
            'businessPhone' => 'required|string',
            'businessAddress' => 'required|string',
            'stateId' => 'nullable|exists:states,id',
            'lgaId' => 'nullable|exists:lgas,id',
        ]);

        $password = Str::password(16);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($password),
            'role' => UserRole::Vendor,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'business_name' => $this->businessName,
            'slug' => Str::slug($this->businessName).'-'.uniqid(),
            'business_phone' => $this->businessPhone,
            'business_address' => $this->businessAddress,
            'state_id' => $this->stateId,
            'lga_id' => $this->lgaId,
            'status' => VendorStatus::Approved,
            'approved_at' => now(),
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        Mail::to($user->email)->queue(new VendorAccountCreatedMail($user, $vendor, $password));

        $this->reset([
            'showForm', 'name', 'email', 'phone',
            'businessName', 'businessPhone', 'businessAddress', 'stateId', 'lgaId',
        ]);
    }

    public function approve(int $vendorId): void
    {
        Vendor::whereKey($vendorId)->update([
            'status' => VendorStatus::Approved,
            'approved_at' => now(),
            'id_document_rejection_reason' => null,
            'selfie_rejection_reason' => null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
    }

    public function suspend(int $vendorId): void
    {
        Vendor::whereKey($vendorId)->update(['status' => VendorStatus::Suspended]);
    }

    /**
     * Ask the vendor to retake/re-upload one of their KYC photos instead of
     * suspending the account outright - the vendor sees the reason on their
     * identity verification page and can resubmit just that photo.
     */
    public function requestRetake(int $vendorId, string $type): void
    {
        abort_unless(in_array($type, ['id', 'selfie'], true), 404);

        $reason = trim($this->rejectionReason[$vendorId][$type] ?? '');

        $this->validate([
            "rejectionReason.{$vendorId}.{$type}" => 'required|string|max:500',
        ], [
            "rejectionReason.{$vendorId}.{$type}.required" => 'Please give the vendor a reason for requesting a retake.',
        ]);

        $vendor = Vendor::findOrFail($vendorId);

        $vendor->update([
            'status' => VendorStatus::Pending,
            ($type === 'id' ? 'id_document_rejection_reason' : 'selfie_rejection_reason') => $reason,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        unset($this->rejectionReason[$vendorId][$type]);

        $label = $type === 'id' ? 'ID document' : 'selfie';

        if ($vendor->business_phone) {
            SendOrderStatusSms::dispatch(
                $vendor->business_phone,
                "Daha Shop: your {$label} photo needs to be retaken ({$reason}). Please log in to your vendor dashboard to resubmit it."
            );
        }

        if ($vendor->user->hasRealEmail()) {
            Mail::to($vendor->user->email)->queue(
                new VendorDocumentRetakeRequested($vendor->business_name, $label, $reason)
            );
        }
    }

    public function render()
    {
        $query = Vendor::with('user')->latest();

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return view('livewire.admin.vendor-approvals', [
            'vendors' => $query->paginate(10),
            'statuses' => VendorStatus::cases(),
            'states' => State::orderBy('name')->get(),
        ]);
    }
}
