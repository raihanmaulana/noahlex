<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $documentName;
    public $inviterName;

    /**
     * Create a new message instance.
     */
    public function __construct($documentName, $inviterName)
    {
        $this->documentName = $documentName;
        $this->inviterName = $inviterName;
    }

    public function build()
    {
        return $this->subject('You have been invited to a document')
            ->view('emails.document_invitation');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Invitation Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
