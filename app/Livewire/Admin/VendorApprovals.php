<?php

namespace App\Livewire\Admin;

use App\Enums\VendorStatus;
use App\Jobs\SendOrderStatusSms;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class VendorApprovals extends Component
{
    use WithPagination;

    public string $filter = 'pending';

    public array $rejectionReason = [];

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

        if ($vendor->business_phone) {
            $label = $type === 'id' ? 'ID document' : 'selfie';

            SendOrderStatusSms::dispatch(
                $vendor->business_phone,
                "Daha Shop: your {$label} photo needs to be retaken ({$reason}). Please log in to your vendor dashboard to resubmit it."
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
        ]);
    }
}
