<?php

namespace App\Providers;

use App\Events\CashCollected;
use App\Events\DeliveryFailed;
use App\Events\OrderConfirmed;
use App\Events\VendorOrderAccepted;
use App\Events\VendorPayoutProcessed;
use App\Listeners\NotifyCustomerOfDeliveryFailed;
use App\Listeners\NotifyCustomerOfOrderConfirmed;
use App\Listeners\NotifyCustomerOfVendorOrderAccepted;
use App\Listeners\NotifyVendorOfCashCollected;
use App\Listeners\NotifyVendorOfPayout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderConfirmed::class => [
            NotifyCustomerOfOrderConfirmed::class,
        ],
        VendorOrderAccepted::class => [
            NotifyCustomerOfVendorOrderAccepted::class,
        ],
        DeliveryFailed::class => [
            NotifyCustomerOfDeliveryFailed::class,
        ],
        CashCollected::class => [
            NotifyVendorOfCashCollected::class,
        ],
        VendorPayoutProcessed::class => [
            NotifyVendorOfPayout::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
