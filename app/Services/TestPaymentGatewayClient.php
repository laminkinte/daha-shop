<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Local-dev-only fake gateway that lets the rest of the app be exercised end
 * to end with no real payment provider credentials configured - the same
 * pattern App\Services\Sms\LogSmsGateway already uses for SMS. PaymentGatewayManager
 * refuses to hand this out outside local/testing environments, so it can
 * never be reached in production even if someone tampers with a form to
 * submit gateway=test.
 *
 * Two modes, chosen by the caller via $context['requiresManualConfirmation']:
 *
 * - Default (subscriptions, delivery-fee payments): the same browser/device
 *   that started the payment is the one that "completes" it, so initialize()
 *   points straight back at the caller-supplied $returnUrl and verification
 *   succeeds immediately - there's no second party to wait on.
 * - Manual confirmation (agent/vendor collecting payment from a customer via
 *   QR): a genuinely different device is expected to complete the payment,
 *   so the transaction stays "pending" - exactly like a real gateway would -
 *   until that device visits the local simulate-payment page and confirms.
 *   This is what makes the QR actually mean something to scan instead of
 *   auto-completing on its own after a few seconds of polling.
 */
class TestPaymentGatewayClient implements PaymentGatewayClient
{
    public function initialize(string $reference, int $amountKobo, string $returnUrl, array $context = []): array
    {
        if ($context['requiresManualConfirmation'] ?? false) {
            Cache::put(self::pendingKey($reference), true, now()->addHour());
            $url = route('test-payment.show', $reference);
        } else {
            $url = $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'reference='.$reference;
        }

        return [
            'authorization_url' => $url,
            'checkoutUrl' => $url,
        ];
    }

    public function verifyTransaction(string $reference): array
    {
        $status = Cache::get(self::pendingKey($reference)) ? 'pending' : 'success';

        return ['status' => $status, 'reference' => $reference];
    }

    public function transactionSucceeded(array $response): bool
    {
        return ($response['status'] ?? null) === 'success';
    }

    public function verifyWebhookSignature(mixed $payload, ?string $signature): bool
    {
        return true;
    }

    /**
     * Called from the local-only simulate-payment page once a "customer"
     * clicks through it - the stand-in for actually paying on a real
     * gateway's hosted checkout.
     */
    public static function confirmManualPayment(string $reference): void
    {
        Cache::forget(self::pendingKey($reference));
    }

    public static function isAwaitingManualConfirmation(string $reference): bool
    {
        return (bool) Cache::get(self::pendingKey($reference));
    }

    private static function pendingKey(string $reference): string
    {
        return "test_payment_pending:{$reference}";
    }
}
