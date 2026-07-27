<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Services\DeliveryPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryPaymentCallbackController extends Controller
{
    public function __invoke(Request $request, DeliveryPaymentService $payments): RedirectResponse
    {
        $reference = (string) $request->query('reference', '');
        if ($reference !== '') {
            $payments->verifyAndComplete($reference);
        }

        // Role-aware smart redirect (vendor -> vendor.dashboard, agent ->
        // agent.deliveries, etc.) since this callback is now shared between
        // agents completing a delivery and vendors completing self-delivery
        // or pickup payment.
        return redirect()->route('dashboard');
    }
}