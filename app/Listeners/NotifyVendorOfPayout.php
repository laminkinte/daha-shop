<?php

namespace App\Listeners;

use App\Events\VendorPayoutProcessed;
use App\Mail\VendorPayoutPaidMail;
use App\Notifications\InAppAlert;
use Illuminate\Support\Facades\Mail;

class NotifyVendorOfPayout
{
    public function handle(VendorPayoutProcessed $event): void
    {
        $payout = $event->payout;

        $payout->vendor->user->notify(new InAppAlert(
            title: 'Payout paid',
            message: 'Your Daha Shop payout of '.naira($payout->total_amount)." for {$payout->period_start->format('M j')}–{$payout->period_end->format('M j')} has been paid.",
            url: route('vendor.payouts'),
        ));

        if ($payout->vendor->user->hasRealEmail()) {
            Mail::to($payout->vendor->user->email)->queue(new VendorPayoutPaidMail($payout));
        }
    }
}
