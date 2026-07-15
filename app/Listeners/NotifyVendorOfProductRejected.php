<?php

namespace App\Listeners;

use App\Events\ProductRejected;
use App\Jobs\SendOrderStatusSms;
use App\Mail\ProductRejectedMail;
use Illuminate\Support\Facades\Mail;

class NotifyVendorOfProductRejected
{
    public function handle(ProductRejected $event): void
    {
        $product = $event->product;
        $vendor = $product->vendor;

        SendOrderStatusSms::dispatch(
            $vendor->business_phone,
            "Daha Shop: your product \"{$product->name}\" was rejected ({$product->rejection_reason}). Edit and resubmit it from your dashboard."
        );

        if ($vendor->user->hasRealEmail()) {
            Mail::to($vendor->user->email)->queue(new ProductRejectedMail($product));
        }
    }
}
