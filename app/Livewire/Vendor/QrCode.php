<?php

namespace App\Livewire\Vendor;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class QrCode extends Component
{
    public function render()
    {
        $vendor = Auth::user()->vendor;
        $shopUrl = route('storefront.vendor', $vendor->slug);

        $result = (new Builder(
            writer: new PngWriter,
            data: $shopUrl,
            size: 400,
            margin: 16,
        ))->build();

        return view('livewire.vendor.qr-code', [
            'vendor' => $vendor,
            'shopUrl' => $shopUrl,
            'qrDataUri' => $result->getDataUri(),
        ]);
    }
}
