<?php

namespace App\Events;

use App\Models\VendorOrder;
use Illuminate\Foundation\Events\Dispatchable;

class DeliveryFailed
{
    use Dispatchable;

    public function __construct(public VendorOrder $vendorOrder) {}
}
