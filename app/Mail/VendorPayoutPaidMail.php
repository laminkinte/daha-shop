<?php

namespace App\Mail;

use App\Models\VendorPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorPayoutPaidMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public VendorPayout $payout) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Daha Shop payout has been paid',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vendor.payout-paid',
        );
    }
}
