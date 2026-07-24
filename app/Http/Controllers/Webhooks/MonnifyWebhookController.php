<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\DeliveryFeePaymentService;
use App\Services\MonnifyClient;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MonnifyWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MonnifyClient $monnify,
        SubscriptionService $subscriptions,
        DeliveryFeePaymentService $deliveryFeePayments,
    ): JsonResponse {
        $payload = $request->json()->all();

        $valid = $monnify->verifyWebhookSignature($payload, $request->header('monnify-signature'));

        if (! $valid) {
            return response()->json(['message' => 'invalid signature'], 401);
        }

        $reference = (string) ($payload['eventData']['paymentReference'] ?? '');

        if (Str::startsWith($reference, 'delfee_')) {
            $deliveryFeePayments->verifyAndActivate($reference);
        } elseif ($reference !== '') {
            $subscriptions->verifyAndActivate($reference);
        }

        return response()->json(['status' => 'ok']);
    }
}
