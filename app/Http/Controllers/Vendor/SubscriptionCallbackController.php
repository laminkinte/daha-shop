<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionCallbackController extends Controller
{
    public function __invoke(Request $request, SubscriptionService $subscriptions): RedirectResponse
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));

        if ($reference === '') {
            return redirect()->route('vendor.subscription')->with('subscription_status', 'failed');
        }

        $subscription = $subscriptions->verifyAndActivate($reference);

        return redirect()->route('vendor.subscription')
            ->with('subscription_status', $subscription?->isActive() ? 'activated' : 'failed');
    }
}
