<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackClient
{
    private const BASE_URL = 'https://api.paystack.co';

    public function initializeTransaction(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata = []): array
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(self::BASE_URL.'/transaction/initialize', [
                'email' => $email,
                'amount' => $amountKobo,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
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

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha512', $payload, (string) config('services.paystack.secret_key'));

        return hash_equals($expected, $signature);
    }
}
