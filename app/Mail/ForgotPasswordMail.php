<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;

    /**
     * Create a new message instance.
     */
    public function __construct($mailData)
    {
        $this->mailData = $mailData;
        $this->afterCommit();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: $this->mailData['title'],
        );

        // Add reply-to address if specified in settings
        if (!empty(config('mail.reply_to.address'))) {
            $envelope->replyTo(
                new Address(
                    config('mail.reply_to.address'),
                    config('mail.reply_to.name')
                )
            );
        }

        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.forgot-password',
            with: [
                'mailData' => $this->mailData,
                'displayDate' => now()->format('F j, Y'),
            ],
        );
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $message = $this->withSymfonyMessage(function ($message) {
            $message->getHeaders()
                ->addTextHeader('X-Environment', app()->environment());
        });

        if (isset($this->mailData['priority'])) {
            $message->priority($this->mailData['priority']);
        }

        return $message;
    }
}
