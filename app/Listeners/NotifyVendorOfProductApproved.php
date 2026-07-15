<?php

namespace App\Listeners;

use App\Events\ProductApproved;
use App\Mail\ProductApprovedMail;
use App\Notifications\InAppAlert;
use Illuminate\Support\Facades\Mail;

class NotifyVendorOfProductApproved
{
    public function handle(ProductApproved $event): void
    {
        $product = $event->product;
        $vendor = $product->vendor;

        $vendor->user->notify(new InAppAlert(
            title: 'Product approved',
            message: "Your product \"{$product->name}\" was approved and is now live.",
            url: route('storefront.product', $product),
        ));

        if ($vendor->user->hasRealEmail()) {
            Mail::to($vendor->user->email)->queue(new ProductApprovedMail($product));
        }
    }
}
