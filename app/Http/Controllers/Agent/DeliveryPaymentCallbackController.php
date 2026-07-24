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

        return redirect()->route('agent.deliveries');
    }
}