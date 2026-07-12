<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Monthly = 'monthly';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Annual => 'Annual',
        };
    }

    public function amountKobo(): int
    {
        return match ($this) {
            self::Monthly => config('subscriptions.plans.monthly.amount'),
            self::Annual => config('subscriptions.plans.annual.amount'),
        };
    }

    public function extend(\Illuminate\Support\Carbon $from): \Illuminate\Support\Carbon
    {
        return match ($this) {
            self::Monthly => $from->copy()->addMonth(),
            self::Annual => $from->copy()->addYear(),
        };
    }
}
