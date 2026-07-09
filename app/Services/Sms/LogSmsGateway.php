<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsGateway implements SmsGatewayInterface
{
    public function send(string $phone, string $message): void
    {
        Log::info("[SMS to {$phone}] {$message}");
    }
}
