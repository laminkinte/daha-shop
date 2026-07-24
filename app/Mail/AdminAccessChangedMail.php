<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminAccessChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $summary) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Daha Shop Admin Access Changed',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.access-changed',
        );
    }
}
