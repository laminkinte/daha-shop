<?php

namespace App\Services;

use RuntimeException;

/**
 * NOT IMPLEMENTED. Kuda's Business API does not offer a hosted-checkout
 * redirect like Paystack/OPay/Monnify - it collects payment via a
 * dynamically-generated virtual account number that the customer bank
 * -transfers into, confirmed by a webhook notification on the incoming
 * transfer. docs.kuda.com / developer.kuda.com are JS-rendered SPAs that
 * could not be read at the time this stub was written, and no real Kuda
 * Business account exists yet to test against.
 *
 * What's known from public search results, to build from:
 * - Developer portal: https://developer.kuda.com/
 * - API docs: https://docs.kuda.com/
 * - Support/getting started: https://business-support.kuda.com/en/collections/10204092-business-api
 * - The API supports "dynamic accounts" for automated incoming payments and
 *   real-time transaction webhooks - exact auth scheme, the virtual-account
 *   creation endpoint, and the webhook payload shape are NOT confirmed.
 *
 * Because this is architecturally different from the other gateways,
 * initialize() is intended to return a DIFFERENT shape once implemented:
 *   ['type' => 'virtual_account', 'account_number' => ..., 'bank_name' => ...,
 *    'account_name' => ..., 'reference' => $reference]
 * instead of a checkout URL - see SubscriptionService::initialize(), which
 * already branches on this 'type' key for exactly this reason. The webhook
 * handler would need to match an incoming transfer notification back to
 * $reference (exact field TBD).
 *
 * Every method below throws until someone with real API docs/sandbox
 * access implements it.
 */
class KudaClient implements PaymentGatewayClient
{
    private const NOT_IMPLEMENTED = 'Kuda integration is not implemented yet - see KudaClient class doc block for what to build (requires real API docs/sandbox access from developer.kuda.com).';

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
