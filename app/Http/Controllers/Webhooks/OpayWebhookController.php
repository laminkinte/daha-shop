<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\DeliveryFeePaymentService;
use App\Services\OpayClient;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OpayWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        OpayClient $opay,
        SubscriptionService $subscriptions,
        DeliveryFeePaymentService $deliveryFeePayments,
    ): JsonResponse {
        $payload = $request->json()->all();

        $valid = $opay->verifyWebhookSignature($payload, $payload['sha512'] ?? null);

        if (! $valid) {
            return response()->json(['message' => 'invalid signature'], 401);
        }

        $reference = (string) ($payload['payload']['reference'] ?? '');

        if (Str::startsWith($reference, 'delfee_')) {
            $deliveryFeePayments->verifyAndActivate($reference);
        } elseif ($reference !== '') {
            $subscriptions->verifyAndActivate($reference);
        }

        return response()->json(['status' => 'ok']);
    }
}
