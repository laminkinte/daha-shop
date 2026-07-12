<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Wraps OPay's Cashier (Checkout) API - the same hosted-checkout product
 * OPay documents for its supported countries, used here for Nigeria
 * (country: NG, currency: NGN). Reference: https://doc.opaycheckout.com/
 *
 * Note: the amount unit (kobo vs whole naira) for the "amount.total" field
 * could not be confirmed without a live sandbox account - this assumes kobo
 * to match every other money value in this app. Verify against a real
 * sandbox transaction once OPay merchant keys are available, before relying
 * on this in production.
 */
class OpayClient
{
    public function createCashierOrder(string $reference, int $amountKobo, string $returnUrl, string $callbackUrl, array $userInfo = []): array
    {
        $body = [
            'country' => 'NG',
            'reference' => $reference,
            'amount' => [
                'total' => $amountKobo,
                'currency' => 'NGN',
            ],
            'returnUrl' => $returnUrl,
            'callbackUrl' => $callbackUrl,
            'userInfo' => $userInfo,
        ];

        $response = Http::withToken(config('services.opay.public_key'))
            ->withHeaders(['MerchantId' => config('services.opay.merchant_id')])
            ->post($this->baseUrl().'/api/v1/international/cashier/create', $body);

        $data = $response->json();

        if (! $response->successful() || ($data['code'] ?? null) !== '00000') {
            throw new RuntimeException('Unable to create OPay cashier order: '.$response->body());
        }

        return $data['data'];
    }

    public function queryStatus(string $reference): array
    {
        $body = ['reference' => $reference, 'country' => 'NG'];
        $signature = $this->signRequest($body);

        $response = Http::withToken($signature)
            ->withHeaders(['MerchantId' => config('services.opay.merchant_id')])
            ->post($this->baseUrl().'/api/v1/international/cashier/status', $body);

        $data = $response->json();

        if (! $response->successful() || ($data['code'] ?? null) !== '00000') {
            throw new RuntimeException('Unable to query OPay payment status: '.$response->body());
        }

        return $data['data'];
    }

    /**
     * HMAC-SHA512 signature of the request body, sorted alphabetically by
     * key, as required for every Cashier endpoint besides "create".
     */
    private function signRequest(array $body): string
    {
        ksort($body);
        $sorted = json_encode($body);

        return hash_hmac('sha512', $sorted, (string) config('services.opay.secret_key'));
    }

    /**
     * Verifies a callback's `sha512` field against the exact field order
     * OPay's docs specify for transaction-status callbacks.
     */
    public function verifyWebhookSignature(array $payload, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $p = $payload['payload'] ?? [];
        $refunded = ($p['refunded'] ?? false) ? 't' : 'f';

        $string = sprintf(
            '{Amount:"%s",Currency:"%s",Reference:"%s",Refunded:%s,Status:"%s",Timestamp:"%s",Token:"%s",TransactionID:"%s"}',
            $p['amount'] ?? '',
            $p['currency'] ?? '',
            $p['reference'] ?? '',
            $refunded,
            $p['status'] ?? '',
            $p['timestamp'] ?? '',
            $p['token'] ?? '',
            $p['transactionId'] ?? '',
        );

        $expected = hash_hmac('sha3-512', $string, (string) config('services.opay.secret_key'));

        return hash_equals($expected, $signature);
    }

    private function baseUrl(): string
    {
        return config('services.opay.sandbox', true)
            ? 'https://sandboxapi.opaycheckout.com'
            : 'https://api.opaycheckout.com';
    }
}
