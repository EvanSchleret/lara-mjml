<?php

namespace LaraMjmlGeneratedNamespace;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LaraMjmlGeneratedClass extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'LaraMjmlGeneratedSubject',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'lara_mjml_generated_view',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
