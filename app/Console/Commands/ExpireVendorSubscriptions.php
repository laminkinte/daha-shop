<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionExpiredMail;
use App\Models\VendorSubscription;
use App\Notifications\InAppAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ExpireVendorSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-vendor-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark vendor subscriptions past their expiry date as expired';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $subscriptions = VendorSubscription::where('status', SubscriptionStatus::Active)
            ->where('expires_at', '<=', now())
            ->with('vendor.user')
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->update(['status' => SubscriptionStatus::Expired]);

            $vendor = $subscription->vendor;

            $vendor->user->notify(new InAppAlert(
                title: 'Subscription expired',
                message: "Your subscription expired on {$subscription->expires_at->format('M j, Y')}. Renew it to keep posting products.",
                url: route('vendor.subscription'),
            ));

            if ($vendor->user->hasRealEmail()) {
                Mail::to($vendor->user->email)->queue(new SubscriptionExpiredMail($subscription));
            }
        }

        $this->info("Marked {$subscriptions->count()} subscription(s) as expired.");

        return self::SUCCESS;
    }
}
