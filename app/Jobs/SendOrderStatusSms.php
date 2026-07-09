<?php

namespace App\Jobs;

use App\Services\Sms\SmsGatewayInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOrderStatusSms implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $phone,
        public string $message,
    ) {}

    public function handle(SmsGatewayInterface $sms): void
    {
        $sms->send($this->phone, $this->message);
    }
}
