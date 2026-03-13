<?php

declare(strict_types=1);

namespace Modules\Content\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Content\Models\SupportRequest;

class SupportRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly SupportRequest $supportRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cerere de suport nouă — ' . $this->supportRequest->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support-request',
            with: ['supportRequest' => $this->supportRequest],
        );
    }
}
