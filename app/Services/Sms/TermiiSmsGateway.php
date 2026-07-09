<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class TermiiSmsGateway implements SmsGatewayInterface
{
    public function send(string $phone, string $message): void
    {
        Http::post('https://api.ng.termii.com/api/sms/send', [
            'to' => $phone,
            'from' => config('services.termii.sender_id'),
            'sms' => $message,
            'type' => 'plain',
            'channel' => 'generic',
            'api_key' => config('services.termii.api_key'),
        ])->throw();
    }
}
