<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ProjectDocument; // <-- Tambahkan ini

class DocumentApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public ProjectDocument $document; // Properti untuk menyimpan data dokumen

    /**
     * Create a new notification instance.
     */
    public function __construct(ProjectDocument $document)
    {
        $this->document = $document; // Terima objek dokumen saat notifikasi dibuat
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Kita akan mengirim via email ('mail') dan database untuk in-app ('database')
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // This method defines the email content to be sent.
        return (new MailMessage)
            ->subject('Document Approved: ' . $this->document->name)
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('Good news! The document you uploaded has been approved.')
            ->line('Document Name: ' . $this->document->name)
            ->line('Version: ' . $this->document->version)
            ->action('View Document', url('/projects/' . $this->document->project_id . '/documents/' . $this->document->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // This method defines the data to be stored in the notifications table (for in-app).
        return [
            'document_id'   => $this->document->id,
            'document_name' => $this->document->name,
            'message'       => 'The document "' . $this->document->name . '" has been approved.',
            'url'           => '/projects/' . $this->document->project_id . '/documents/' . $this->document->id,
        ];
    }
}
