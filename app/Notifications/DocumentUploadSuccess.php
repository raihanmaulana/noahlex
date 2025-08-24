<?php

namespace App\Notifications;

use App\Models\ProjectDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentUploadSuccess extends Notification implements ShouldQueue
{
    use Queueable;

    protected $document;

    public function __construct(ProjectDocument $document)
    {
        $this->document = $document;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document Uploaded!') 
            ->view('emails.document_upload_success', [
                'documentName' => $this->document->original_name,
                'projectLabel' => 'Project #' . $this->document->project_id, 
                // 'sizeHuman'    => \Illuminate\Support\Number::fileSize($this->document->size_bytes ?? 0),
                'uploadedAt'   => optional($this->document->created_at)->format('Y-m-d H:i:s'),
                'viewUrl'      => url("/projects/{$this->document->project_id}/documents/{$this->document->id}"),
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'document_id'   => $this->document->id,
            'document_name' => $this->document->original_name,
            'message'       => 'Your document was uploaded successfully.',
        ];
    }
}
