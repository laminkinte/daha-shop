<?php

namespace App\Services;

use App\Enums\PaymentGateway;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Events\SubscriptionActivated;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function __construct(
        private PaystackClient $paystack,
        private OpayClient $opay,
    ) {}

    /**
     * Create a pending subscription row and start a transaction with the
     * chosen gateway. Returns the URL the vendor should be redirected to.
     */
    public function initialize(Vendor $vendor, SubscriptionPlan $plan, PaymentGateway $gateway, string $returnUrl): string
    {
        $reference = 'sub_'.$vendor->id.'_'.Str::random(16);

        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'gateway' => $gateway,
            'plan' => $plan,
            'amount' => $plan->amountKobo(),
            'status' => SubscriptionStatus::Pending,
            'reference' => $reference,
        ]);

        if ($gateway === PaymentGateway::Opay) {
            // Append our reference explicitly rather than relying on however
            // OPay names its own query params on redirect back.
            $opayReturnUrl = $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'reference='.$reference;

            $data = $this->opay->createCashierOrder(
                reference: $reference,
                amountKobo: $subscription->amount,
                returnUrl: $opayReturnUrl,
                callbackUrl: route('webhooks.opay'),
                userInfo: [
                    'userId' => (string) $vendor->user_id,
                    'userName' => $vendor->business_name,
                    'userMobile' => $vendor->business_phone,
                    'userEmail' => $vendor->user->email,
                ],
            );

            return $data['cashierUrl'];
        }

        $data = $this->paystack->initializeTransaction(
            email: $vendor->user->email,
            amountKobo: $subscription->amount,
            reference: $reference,
            callbackUrl: $returnUrl,
            metadata: ['vendor_id' => $vendor->id, 'plan' => $plan->value],
        );

        return $data['authorization_url'];
    }

    /**
     * Verify a transaction reference against its gateway and activate the
     * subscription if payment succeeded. Safe to call more than once for the
     * same reference (from both the browser callback and the webhook).
     */
    public function verifyAndActivate(string $reference): ?VendorSubscription
    {
        $subscription = VendorSubscription::where('reference', $reference)->first();

        if (! $subscription || $subscription->isActive()) {
            return $subscription;
        }

        $succeeded = $subscription->gateway === PaymentGateway::Opay
            ? ($this->opay->queryStatus($reference)['status'] ?? null) === 'SUCCESS'
            : ($this->paystack->verifyTransaction($reference)['status'] ?? null) === 'success';

        if (! $succeeded) {
            $subscription->update(['status' => SubscriptionStatus::Failed]);

            return $subscription;
        }

        $this->activate($subscription);

        return $subscription->fresh();
    }

    /**
     * Activate a subscription whose payment is already confirmed.
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

        SubscriptionActivated::dispatch($subscription->fresh());
    }
}
