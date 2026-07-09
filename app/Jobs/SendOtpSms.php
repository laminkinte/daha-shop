<?php

namespace App\Jobs;

use App\Services\Sms\SmsGatewayInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOtpSms implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $phone,
        public string $code,
    ) {}

    public function handle(SmsGatewayInterface $sms): void
    {
        $sms->send($this->phone, "Your MarketHub NG confirmation code is {$this->code}. It expires in ".config('markethub.otp.expires_in_minutes').' minutes.');
    }
}
