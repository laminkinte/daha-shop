<?php

namespace App\Listeners;

use App\Events\SubscriptionActivated;
use App\Mail\SubscriptionActivatedMail;
use App\Notifications\InAppAlert;
use Illuminate\Support\Facades\Mail;

class NotifyVendorOfSubscriptionActivated
{
    public function handle(SubscriptionActivated $event): void
    {
        $subscription = $event->subscription;
        $vendor = $subscription->vendor;

        $vendor->user->notify(new InAppAlert(
            title: 'Subscription active',
            message: "Your Daha Shop subscription is now active until {$subscription->expires_at->format('M j, Y')}.",
            url: route('vendor.subscription'),
        ));

        if ($vendor->user->hasRealEmail()) {
            Mail::to($vendor->user->email)->queue(new SubscriptionActivatedMail($subscription));
        }
    }
}
