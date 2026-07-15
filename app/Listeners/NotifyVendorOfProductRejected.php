<?php

namespace App\Listeners;

use App\Events\ProductRejected;
use App\Mail\ProductRejectedMail;
use App\Notifications\InAppAlert;
use Illuminate\Support\Facades\Mail;

class NotifyVendorOfProductRejected
{
    public function handle(ProductRejected $event): void
    {
        $product = $event->product;
        $vendor = $product->vendor;

        $vendor->user->notify(new InAppAlert(
            title: 'Product rejected',
            message: "Your product \"{$product->name}\" was rejected ({$product->rejection_reason}). Edit and resubmit it from your dashboard.",
            url: route('vendor.products'),
        ));

        if ($vendor->user->hasRealEmail()) {
            Mail::to($vendor->user->email)->queue(new ProductRejectedMail($product));
        }
    }
}
