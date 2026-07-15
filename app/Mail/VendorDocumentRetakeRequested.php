<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorDocumentRetakeRequested extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $businessName,
        public string $documentLabel,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Action needed: retake your {$this->documentLabel} photo",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vendor.document-retake-requested',
        );
    }
}
