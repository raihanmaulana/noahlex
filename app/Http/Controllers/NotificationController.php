<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        $query = Notification::where('user_id', auth()->id());

        if ($request->has('read')) {
            if ($request->read === 'true') {
                $query->where('is_read', true);
            } elseif ($request->read === 'false') {
                $query->where('is_read', false);
            }
        }

        $notifications = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil diambil.',
            'data' => $notifications
        ]);
    }


    public function markAsRead(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:notifications,id'
        ]);

        $notif = Notification::where('id', $request->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$notif) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan atau bukan milik Anda.'
            ], 404);
        }

        $notif->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi telah ditandai sebagai dibaca.',
            'data' => $notif
        ]);
    }
}
