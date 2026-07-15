<?php

namespace App\Mail;

use App\Models\VendorSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionActivatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public VendorSubscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Daha Shop subscription is active',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vendor.subscription-activated',
        );
    }
}
