<?php

use App\Models\Notification;

if (!function_exists('sendNotification')) {
    function sendNotification($userId, $title, $message = null)
    {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message
        ]);
    }
}
