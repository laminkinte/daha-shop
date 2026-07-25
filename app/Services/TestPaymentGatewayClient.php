<?php

namespace App\Services;

/**
 * Local-dev-only fake gateway that instantly "succeeds" without contacting
 * any real payment provider - the same pattern App\Services\Sms\LogSmsGateway
 * already uses for SMS: something works out of the box on a machine with no
 * real API credentials configured, so the rest of the app can be tested end
 * to end. PaymentGatewayManager refuses to hand this out outside local/testing
 * environments, so it can never be reached in production even if someone
 * tampers with a form to submit gateway=test.
 *
 * initialize() doesn't redirect anywhere external - it points straight back
 * at the caller-supplied $returnUrl with the reference attached, simulating
 * what a real gateway's hosted checkout would do after a successful payment.
 */
class TestPaymentGatewayClient implements PaymentGatewayClient
{
    public function initialize(string $reference, int $amountKobo, string $returnUrl, array $context = []): array
    {
        $url = $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'reference='.$reference;

        return [
            'authorization_url' => $url,
            'checkoutUrl' => $url,
        ];
    }

    public function verifyTransaction(string $reference): array
    {
        return ['status' => 'success', 'reference' => $reference];
    }

    public function transactionSucceeded(array $response): bool
    {
        return true;
    }

    public function verifyWebhookSignature(mixed $payload, ?string $signature): bool
    {
        return true;
    }
}
