<?php

namespace App\Events;

use App\Models\VendorPayout;
use Illuminate\Foundation\Events\Dispatchable;

class VendorPayoutProcessed
{
    use Dispatchable;

    public function __construct(public VendorPayout $payout) {}
}
