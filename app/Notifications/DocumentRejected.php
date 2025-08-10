<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ProjectDocument;

class DocumentRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public ProjectDocument $document;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProjectDocument $document)
    {
        $this->document = $document;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Kirim ke email dan simpan di database (untuk in-app)
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document Rejected: ' . $this->document->name)
            ->greeting('Hello, ' . $notifiable->name . '.')
            ->line('There is an update regarding a document you uploaded.')
            ->line('The document named "' . $this->document->name . '" has been rejected.')
            ->line('Please review your document and upload a revised version if necessary.')
            ->action('View Document', url('/projects/' . $this->document->project_id . '/documents/' . $this->document->id))
            ->line('Thank you.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'document_id'   => $this->document->id,
            'document_name' => $this->document->name,
            'message'       => 'The document "' . $this->document->name . '" has been rejected.',
            'url'           => '/projects/' . $this->document->project_id . '/documents/' . $this->document->id,
        ];
    }
}
