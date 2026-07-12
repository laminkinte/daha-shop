<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\VendorSubscription;
use Illuminate\Console\Command;

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
        $count = VendorSubscription::where('status', SubscriptionStatus::Active)
            ->where('expires_at', '<=', now())
            ->update(['status' => SubscriptionStatus::Expired]);

        $this->info("Marked {$count} subscription(s) as expired.");

        return self::SUCCESS;
    }
}
