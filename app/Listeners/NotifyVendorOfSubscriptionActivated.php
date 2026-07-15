<?php

namespace App\Listeners;

use App\Events\SubscriptionActivated;
use App\Jobs\SendOrderStatusSms;
use App\Mail\SubscriptionActivatedMail;
use Illuminate\Support\Facades\Mail;

class NotifyVendorOfSubscriptionActivated
{
    public function handle(SubscriptionActivated $event): void
    {
        $subscription = $event->subscription;
        $vendor = $subscription->vendor;

        SendOrderStatusSms::dispatch(
            $vendor->business_phone,
            "Daha Shop: your subscription is now active until {$subscription->expires_at->format('M j, Y')}."
        );

        if ($vendor->user->hasRealEmail()) {
            Mail::to($vendor->user->email)->queue(new SubscriptionActivatedMail($subscription));
        }
    }
}
