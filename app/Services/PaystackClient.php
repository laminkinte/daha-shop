<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackClient implements PaymentGatewayClient
{
    private const BASE_URL = 'https://api.paystack.co';

    public function initialize(string $reference, int $amountKobo, string $returnUrl, array $context = []): array
    {
        $email = $context['email'];
        $metadata = $context['metadata'] ?? [];

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(self::BASE_URL.'/transaction/initialize', [
                'email' => $email,
                'amount' => $amountKobo,
                'reference' => $reference,
                'callback_url' => $returnUrl,
                'metadata' => $metadata,
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new RuntimeException('Unable to initialize Paystack transaction: '.$response->body());
        }

        return $response->json('data');
    }

    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(self::BASE_URL.'/transaction/verify/'.rawurlencode($reference));

        if (! $response->successful() || ! $response->json('status')) {
            throw new RuntimeException('Unable to verify Paystack transaction: '.$response->body());
        }

        return $response->json('data');
    }

    public function transactionSucceeded(array $response): bool
    {
        return ($response['status'] ?? null) === 'success';
    }

    public function verifyWebhookSignature(mixed $payload, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha512', $payload, (string) config('services.paystack.secret_key'));

        return hash_equals($expected, $signature);
    }
}
