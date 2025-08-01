<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProjectDocument;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class CheckDocumentExpiry extends Command
{
    protected $signature = 'documents:check-expiry';
    protected $description = 'Check document expiry dates and send reminders';

    public function handle()
    {
        $today = now()->startOfDay();
        $reminderDays = 30;

        $documents = ProjectDocument::whereNotNull('expiry_date')
            ->where('isDeleted', false)
            ->get();

        foreach ($documents as $doc) {
            $daysLeft = $today->diffInDays($doc->expiry_date, false);

            if ($daysLeft === $reminderDays) {
                $user = User::find($doc->uploaded_by);

                if ($doc->reminder_in_app && $user) {
                    
                    Notification::create([
                        'user_id' => $user->id,
                        'title' => 'Document Expiry Reminder',
                        'message' => "Document '{$doc->name}' will expire in {$reminderDays} days.",
                        'is_read' => false
                    ]);
                }

                if ($doc->reminder_email && $user) {
                    Mail::raw(
                        "Reminder: Document '{$doc->name}' will expire in {$reminderDays} days.",
                        function ($message) use ($user) {
                            $message->to($user->email)
                                ->subject('Document Expiry Reminder');
                        }
                    );
                }
            }
        }

        $this->info('Document expiry check completed.');
    }
}
