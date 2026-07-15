<?php

namespace App\Providers;

use App\Events\AgentAssignedToDelivery;
use App\Events\CashCollected;
use App\Events\CashRemitted;
use App\Events\DeliveryFailed;
use App\Events\OrderCancelled;
use App\Events\OrderConfirmed;
use App\Events\OrderRejected;
use App\Events\ProductApproved;
use App\Events\ProductRejected;
use App\Events\SubscriptionActivated;
use App\Events\VendorOrderAccepted;
use App\Events\VendorOrderRejected;
use App\Events\VendorPayoutProcessed;
use App\Listeners\NotifyAgentOfDeliveryAssignment;
use App\Listeners\NotifyCustomerOfDeliveryFailed;
use App\Listeners\NotifyCustomerOfOrderCancelled;
use App\Listeners\NotifyCustomerOfOrderConfirmed;
use App\Listeners\NotifyCustomerOfOrderRejected;
use App\Listeners\NotifyCustomerOfVendorOrderAccepted;
use App\Listeners\NotifyCustomerOfVendorOrderRejected;
use App\Listeners\NotifyVendorOfCashCollected;
use App\Listeners\NotifyVendorOfCashRemitted;
use App\Listeners\NotifyVendorOfPayout;
use App\Listeners\NotifyVendorOfProductApproved;
use App\Listeners\NotifyVendorOfProductRejected;
use App\Listeners\NotifyVendorOfSubscriptionActivated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderConfirmed::class => [
            NotifyCustomerOfOrderConfirmed::class,
        ],
        OrderRejected::class => [
            NotifyCustomerOfOrderRejected::class,
        ],
        OrderCancelled::class => [
            NotifyCustomerOfOrderCancelled::class,
        ],
        VendorOrderAccepted::class => [
            NotifyCustomerOfVendorOrderAccepted::class,
        ],
        VendorOrderRejected::class => [
            NotifyCustomerOfVendorOrderRejected::class,
        ],
        DeliveryFailed::class => [
            NotifyCustomerOfDeliveryFailed::class,
        ],
        AgentAssignedToDelivery::class => [
            NotifyAgentOfDeliveryAssignment::class,
        ],
        CashCollected::class => [
            NotifyVendorOfCashCollected::class,
        ],
        CashRemitted::class => [
            NotifyVendorOfCashRemitted::class,
        ],
        VendorPayoutProcessed::class => [
            NotifyVendorOfPayout::class,
        ],
        ProductApproved::class => [
            NotifyVendorOfProductApproved::class,
        ],
        ProductRejected::class => [
            NotifyVendorOfProductRejected::class,
        ],
        SubscriptionActivated::class => [
            NotifyVendorOfSubscriptionActivated::class,
        ],
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
