<?php

namespace App\Http\Controllers;

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

    public function pay(string $reference): RedirectResponse
    {
        TestPaymentGatewayClient::confirmManualPayment($reference);

        return redirect()->route('test-payment.show', $reference);
    }
}
