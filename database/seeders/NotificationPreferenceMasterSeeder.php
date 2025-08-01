<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class NotificationPreferenceMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $data = [
            [
                'key' => 'doc_sent',
                'label' => 'New document is sent to me',
                'description' => 'You will be notified when a new document is sent to your account.'
            ],
            [
                'key' => 'doc_opened_first',
                'label' => 'Document is opened by a recipient for the first time',
                'description' => 'Triggered only when a document is opened for the first time by a recipient.'
            ],
            [
                'key' => 'doc_opened_every',
                'label' => 'Document is opened by a recipient every time',
                'description' => 'You will be notified every time a document is opened by any recipient.'
            ],
            [
                'key' => 'doc_approved',
                'label' => 'Document is approved',
                'description' => 'You will be notified once the document is approved.'
            ],
            [
                'key' => 'doc_rejected',
                'label' => 'Document is rejected',
                'description' => 'You will be notified once the document is rejected.'
            ],
            [
                'key' => 'doc_commented',
                'label' => 'Comment is added to document',
                'description' => 'You will be notified whenever someone comments on your document.'
            ],
            [
                'key' => 'doc_sent_on_behalf',
                'label' => 'Any message or notifications for documents sent on my behalf',
                'description' => 'You will be notified when documents are sent or responded to on your behalf.'
            ]
        ];


        DB::table('notification_preference_masters')->insert($data);
    }
}
