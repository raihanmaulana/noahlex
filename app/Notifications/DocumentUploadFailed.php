<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentUploadFailed extends Notification implements ShouldQueue
{
    use Queueable;

    protected $documentName;
    protected $errorMessage;

    public function __construct(string $documentName, string $errorMessage)
    {
        $this->documentName = $documentName;
        $this->errorMessage = $errorMessage;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database']; 
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document Upload Failed')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Unfortunately, your document **{$this->documentName}** failed to upload.")
            ->line("Error message: {$this->errorMessage}")
            ->line('Please try again or contact support if the issue persists.')
            ->salutation('Regards, Document Management System');
    }

    public function toArray($notifiable): array
    {
        return [
            'document_name' => $this->documentName,
            'error'         => $this->errorMessage,
            'message'       => 'Your document failed to upload.',
        ];
    }
}
