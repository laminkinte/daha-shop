<?php

namespace App\Services;

interface PaymentGatewayClient
{
    public function initialize(
        string $reference,
        int $amountKobo,
        string $returnUrl,
        array $context = []
    ): array;

    public function verifyTransaction(string $reference): array;

    public function verifyWebhookSignature(mixed $payload, ?string $signature): bool;

    public function transactionSucceeded(array $response): bool;
}