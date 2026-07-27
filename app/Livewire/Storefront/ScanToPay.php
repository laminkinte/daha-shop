<?php

namespace App\Livewire\Storefront;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class ScanToPay extends Component
{
    public function render()
    {
        return view('livewire.storefront.scan-to-pay');
    }
}
