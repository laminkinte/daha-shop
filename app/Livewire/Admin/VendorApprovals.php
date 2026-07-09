<?php

namespace App\Livewire\Admin;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class VendorApprovals extends Component
{
    use WithPagination;

    public string $filter = 'pending';

    public function approve(int $vendorId): void
    {
        Vendor::whereKey($vendorId)->update(['status' => VendorStatus::Approved, 'approved_at' => now()]);
    }

    public function suspend(int $vendorId): void
    {
        Vendor::whereKey($vendorId)->update(['status' => VendorStatus::Suspended]);
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
