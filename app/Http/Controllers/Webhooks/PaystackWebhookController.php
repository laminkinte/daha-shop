<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\PaystackClient;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackClient $paystack, SubscriptionService $subscriptions): JsonResponse
    {
        $valid = $paystack->verifyWebhookSignature(
            $request->getContent(),
            $request->header('x-paystack-signature'),
        );

        if (! $valid) {
            return response()->json(['message' => 'invalid signature'], 401);
        }

        if ($request->input('event') === 'charge.success') {
            $reference = (string) $request->input('data.reference');

            if ($reference !== '') {
                $subscriptions->verifyAndActivate($reference);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
