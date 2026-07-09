<?php

namespace App\Services;

use App\Jobs\SendOtpSms;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function generate(string $phone, string $purpose = 'order_confirmation'): OtpVerification
    {
        $code = str_pad((string) random_int(0, (10 ** config('markethub.otp.length')) - 1), config('markethub.otp.length'), '0', STR_PAD_LEFT);

        $otp = OtpVerification::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(config('markethub.otp.expires_in_minutes')),
        ]);

        SendOtpSms::dispatch($phone, $code);

        return $otp;
    }

    public function verify(string $phone, string $purpose, string $code): bool
    {
        $otp = OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->isExpired() || $otp->attempts >= config('markethub.otp.max_attempts')) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->update(['verified_at' => now()]);

        return true;
    }
}
