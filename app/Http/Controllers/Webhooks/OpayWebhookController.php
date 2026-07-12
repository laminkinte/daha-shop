<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\OpayClient;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpayWebhookController extends Controller
{
    public function __invoke(Request $request, OpayClient $opay, SubscriptionService $subscriptions): JsonResponse
    {
        $payload = $request->json()->all();

        $valid = $opay->verifyWebhookSignature($payload, $payload['sha512'] ?? null);

        if (! $valid) {
            return response()->json(['message' => 'invalid signature'], 401);
        }

        $reference = (string) ($payload['payload']['reference'] ?? '');

        if ($reference !== '') {
            $subscriptions->verifyAndActivate($reference);
        }

        return response()->json(['status' => 'ok']);
    }
}
