<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private string $emailBody,
        private string $emailSubject,
        private ?string $altBody = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.generic',
            text: $this->altBody ? 'emails.generic-text' : null,
            with: [
                'emailBody' => $this->emailBody,
                'emailSubject' => $this->emailSubject,
                'altBody' => $this->altBody ?? $this->generateAltBody(),
            ]
        );
    }

    protected function generateAltBody(): string
    {
        $text = strip_tags($this->emailBody);
        $text = html_entity_decode($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/<a\s+href="([^"]+)"[^>]*>(.*?)<\/a>/i', '$2 ($1)', $text);
        return trim($text);
    }
}
