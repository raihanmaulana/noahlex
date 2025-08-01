<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserNotificationPreference;

class UserNotificationPreferenceSeeder extends Seeder
{
    public function run()
    {
        $userId = 1;

        $preferences = [
            'doc_sent',
            'doc_opened_first',
            'doc_opened_every',
            'doc_approved',
            'doc_rejected',
            'doc_commented',
            'doc_sent_on_behalf'
        ];

        foreach ($preferences as $key) {
            UserNotificationPreference::updateOrCreate(
                ['user_id' => $userId, 'key' => $key],
                ['enabled' => true]
            );
        }
    }
}


