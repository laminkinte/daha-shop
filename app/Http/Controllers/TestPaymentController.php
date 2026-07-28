<?php

namespace App\Http\Controllers;

use App\Services\DeliveryPaymentService;
use App\Services\TestPaymentGatewayClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TestPaymentController extends Controller
{
    public function show(string $reference): View
    {
        return view('test-payment', [
            'reference' => $reference,
            'awaitingConfirmation' => TestPaymentGatewayClient::isAwaitingManualConfirmation($reference),
        ]);
    }

    public function pay(string $reference, DeliveryPaymentService $payments): RedirectResponse
    {
        TestPaymentGatewayClient::confirmManualPayment($reference);

        // Complete the order right away instead of waiting on the
        // agent/vendor screen's next 5s poll to notice - that screen may not
        // even be open, and "simulate payment" should behave like a real
        // instant payment confirmation, not one with a delivery lag.
        $payments->verifyAndComplete($reference);

        return redirect()->route('test-payment.show', $reference);
    }
}
