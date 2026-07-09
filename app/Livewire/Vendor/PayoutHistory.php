<?php

namespace App\Livewire\Vendor;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class PayoutHistory extends Component
{
    use WithPagination;

    public function render()
    {
        $payouts = Auth::user()->vendor->payouts()->latest('period_end')->paginate(10);

        return view('livewire.vendor.payout-history', ['payouts' => $payouts]);
    }
}
