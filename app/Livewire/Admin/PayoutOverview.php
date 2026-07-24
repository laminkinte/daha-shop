<?php

namespace App\Livewire\Admin;

use App\Enums\PayoutStatus;
use App\Models\VendorPayout;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class PayoutOverview extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public function render()
    {
        $query = VendorPayout::with('vendor')->latest();

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return view('livewire.admin.payout-overview', [
            'payouts' => $query->paginate(10),
            'statuses' => PayoutStatus::cases(),
        ]);
    }
}
