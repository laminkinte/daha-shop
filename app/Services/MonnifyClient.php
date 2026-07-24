<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Wraps Monnify's hosted-checkout ("Invoice"/"Transaction") API - Monnify is
 * the actual payment-gateway product behind the "MoniePoint" brand
 * (MoniePoint itself does not expose a merchant payment API; Monnify is the
 * TeamApt/Moniepoint gateway product businesses integrate with, similar in
 * shape to Paystack). Reference: https://developers.monnify.com/
 *
 * IMPORTANT: this was built from general knowledge of Monnify's typical API
 * shape (OAuth2 client-credentials token exchange, an init-transaction call
 * returning a `checkoutUrl`, a transaction-status lookup, and a webhook
 * verified via a hashed-body signature), NOT from freshly-fetched live
 * documentation - the doc site is a JS-rendered SPA that could not be read
 * at the time this was written. Every field name, endpoint path, and the
 * exact webhook hash formula below must be verified against a real Monnify
 * sandbox account before this is trusted in production - same caveat as
 * OpayClient's own unverified amount-unit assumption.
 */
class MonnifyClient implements PaymentGatewayClient
{
    public function initialize(string $reference, int $amountKobo, string $returnUrl, array $context = []): array
    {
        $email = $context['email'];

        $response = Http::withToken($this->accessToken())
            ->post($this->baseUrl().'/api/v1/merchant/transactions/init-transaction', [
                'amount' => $amountKobo / 100,
                'customerName' => $context['customerName'] ?? $email,
                'customerEmail' => $email,
                'paymentReference' => $reference,
                'paymentDescription' => $context['description'] ?? 'Daha Shop payment',
                'currencyCode' => 'NGN',
                'contractCode' => config('services.monnify.contract_code'),
                'redirectUrl' => $returnUrl,
            ]);

        $data = $response->json();

        if (! $response->successful() || ! ($data['requestSuccessful'] ?? false)) {
            throw new RuntimeException('Unable to initialize Monnify transaction: '.$response->body());
        }

        return $data['responseBody'];
    }

    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->accessToken())
            ->get($this->baseUrl().'/api/v2/transactions/'.rawurlencode($reference));

        $data = $response->json();

        if (! $response->successful() || ! ($data['requestSuccessful'] ?? false)) {
            throw new RuntimeException('Unable to verify Monnify transaction: '.$response->body());
        }

        return $data['responseBody'];
    }

    public function transactionSucceeded(array $response): bool
    {
        return ($response['paymentStatus'] ?? null) === 'PAID';
    }

    /**
     * UNVERIFIED: Monnify's docs describe a "transaction hash" header
     * computed over clientSecret + a set of transaction fields, hashed with
     * SHA512. The exact field concatenation order below is a best guess and
     * must be confirmed against real documentation/sandbox traffic.
     */
    public function verifyWebhookSignature(mixed $payload, ?string $signature): bool
    {
        if (! $signature || ! is_array($payload)) {
            return false;
        }

        $body = $payload['eventData'] ?? [];

        $string = implode('|', [
            config('services.monnify.api_key'),
            $body['transactionReference'] ?? '',
            $body['paymentReference'] ?? '',
            $body['amountPaid'] ?? '',
            $body['paidOn'] ?? '',
            config('services.monnify.secret_key'),
        ]);

        $expected = hash_hmac('sha512', $string, (string) config('services.monnify.secret_key'));

        return hash_equals($expected, $signature);
    }

    /**
     * OAuth2 client-credentials token, cached for slightly under its
     * documented ~1 hour lifetime. UNVERIFIED: exact token endpoint path and
     * expiry field name need confirming against real docs.
     */
    private function accessToken(): string
    {
        return Cache::remember('monnify_access_token', now()->addMinutes(50), function () {
            $response = Http::withBasicAuth(
                config('services.monnify.api_key'),
                config('services.monnify.secret_key'),
            )->post($this->baseUrl().'/api/v1/auth/login');

            $data = $response->json();

            if (! $response->successful() || ! ($data['requestSuccessful'] ?? false)) {
                throw new RuntimeException('Unable to authenticate with Monnify: '.$response->body());
            }

            return $data['responseBody']['accessToken'];
        });
    }

    private function baseUrl(): string
    {
        return config('services.monnify.sandbox', true)
            ? 'https://sandbox.monnify.com'
            : 'https://api.monnify.com';
    }
}
