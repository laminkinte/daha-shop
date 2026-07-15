<?php

namespace App\Listeners;

use App\Events\ProductApproved;
use App\Jobs\SendOrderStatusSms;
use App\Mail\ProductApprovedMail;
use Illuminate\Support\Facades\Mail;

class NotifyVendorOfProductApproved
{
    public function handle(ProductApproved $event): void
    {
        $product = $event->product;
        $vendor = $product->vendor;

        SendOrderStatusSms::dispatch(
            $vendor->business_phone,
            "Daha Shop: your product \"{$product->name}\" was approved and is now live."
        );

        if ($vendor->user->hasRealEmail()) {
            Mail::to($vendor->user->email)->queue(new ProductApprovedMail($product));
        }
    }
}
