<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorAccountCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Vendor $vendor, public string $password) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Daha Shop Vendor Account is Ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vendor.account-created',
        );
    }
}
