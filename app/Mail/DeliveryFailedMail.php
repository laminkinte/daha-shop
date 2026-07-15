<?php

namespace App\Mail;

use App\Models\VendorOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryFailedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public VendorOrder $vendorOrder) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Delivery attempt failed for order #{$this->vendorOrder->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.delivery-failed',
        );
    }
}
