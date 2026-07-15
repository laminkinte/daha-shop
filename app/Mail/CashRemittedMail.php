<?php

namespace App\Mail;

use App\Models\CashReconciliation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CashRemittedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CashReconciliation $reconciliation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cash remitted and reconciled',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vendor.cash-remitted',
        );
    }
}
