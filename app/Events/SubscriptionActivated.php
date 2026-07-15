<?php

namespace App\Events;

use App\Models\VendorSubscription;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionActivated
{
    use Dispatchable;

    public function __construct(public VendorSubscription $subscription) {}
}
