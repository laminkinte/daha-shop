<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Jobs\SendOrderStatusSms;
use App\Mail\SubscriptionExpiredMail;
use App\Models\VendorSubscription;
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

            SendOrderStatusSms::dispatch(
                $vendor->business_phone,
                "Daha Shop: your subscription expired on {$subscription->expires_at->format('M j, Y')}. Renew it to keep posting products."
            );

            if ($vendor->user->hasRealEmail()) {
                Mail::to($vendor->user->email)->queue(new SubscriptionExpiredMail($subscription));
            }
        }

        $this->info("Marked {$subscriptions->count()} subscription(s) as expired.");

        return self::SUCCESS;
    }
}
