<?php

namespace App\Services;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function __construct(private PaystackClient $paystack) {}

    /**
     * Create a pending subscription row and start a Paystack transaction for it.
     * Returns the authorization_url the vendor should be redirected to.
     */
    public function initialize(Vendor $vendor, SubscriptionPlan $plan, string $callbackUrl): string
    {
        $reference = 'sub_'.$vendor->id.'_'.Str::random(16);

        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => $plan,
            'amount' => $plan->amountKobo(),
            'status' => SubscriptionStatus::Pending,
            'paystack_reference' => $reference,
        ]);

        $data = $this->paystack->initializeTransaction(
            email: $vendor->user->email,
            amountKobo: $subscription->amount,
            reference: $reference,
            callbackUrl: $callbackUrl,
            metadata: ['vendor_id' => $vendor->id, 'plan' => $plan->value],
        );

        return $data['authorization_url'];
    }

    /**
     * Verify a transaction reference against Paystack and activate the
     * subscription if payment succeeded. Safe to call more than once for the
     * same reference (from both the browser callback and the webhook).
     */
    public function verifyAndActivate(string $reference): ?VendorSubscription
    {
        $subscription = VendorSubscription::where('paystack_reference', $reference)->first();

        if (! $subscription || $subscription->isActive()) {
            return $subscription;
        }

        $data = $this->paystack->verifyTransaction($reference);

        if (($data['status'] ?? null) !== 'success') {
            $subscription->update(['status' => SubscriptionStatus::Failed]);

            return $subscription;
        }

        $this->activate($subscription);

        return $subscription->fresh();
    }

    /**
     * Activate a subscription whose payment is already confirmed (used by the
     * webhook, which carries its own confirmed charge.success payload).
     */
    public function activate(VendorSubscription $subscription): void
    {
        if ($subscription->status === SubscriptionStatus::Active) {
            return;
        }

        $vendor = $subscription->vendor;
        $currentExpiry = $vendor->activeSubscription()?->expires_at;
        $startsFrom = $currentExpiry && $currentExpiry->isFuture() ? $currentExpiry : now();

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'paid_at' => now(),
            'starts_at' => now(),
            'expires_at' => $subscription->plan->extend($startsFrom),
        ]);
    }
}
