<?php

namespace App\Services;

use RuntimeException;

/**
 * NOT IMPLEMENTED. PalmPay's business payment ("Payin") API could not be
 * verified - docs.palmpay.com is a JS-rendered SPA that could not be read
 * at the time this stub was written, and no real PalmPay merchant account
 * exists yet to test against.
 *
 * What's known from public search results, to build from:
 * - Docs: https://docs.palmpay.com/
 * - Business/merchant portal: https://business.palmpay.com/
 * - Payment acceptance product: https://www.palmpay.com/business/payin/
 * - The API appears to expose `PaymentNotification` and `QueryTxnStatus`
 *   endpoints, supporting both plain and ENCRYPTED request/response bodies
 *   with an authorization header - the exact encryption scheme (algorithm,
 *   key exchange), endpoint URLs, and field names are NOT confirmed.
 *
 * Every method below throws until someone with real API docs/sandbox
 * access implements it - this is deliberate: guessing at the encryption
 * scheme or field names for a real payment integration is worse than an
 * honest "not built yet."
 */
class PalmPayClient implements PaymentGatewayClient
{
    private const NOT_IMPLEMENTED = 'PalmPay integration is not implemented yet - see PalmPayClient class doc block for what to build (requires real API docs/sandbox access from business.palmpay.com).';

    public function initialize(string $reference, int $amountKobo, string $returnUrl, array $context = []): array
    {
        throw new RuntimeException(self::NOT_IMPLEMENTED);
    }

    public function verifyTransaction(string $reference): array
    {
        throw new RuntimeException(self::NOT_IMPLEMENTED);
    }

    public function transactionSucceeded(array $response): bool
    {
        throw new RuntimeException(self::NOT_IMPLEMENTED);
    }

    public function verifyWebhookSignature(mixed $payload, ?string $signature): bool
    {
        throw new RuntimeException(self::NOT_IMPLEMENTED);
    }
}
