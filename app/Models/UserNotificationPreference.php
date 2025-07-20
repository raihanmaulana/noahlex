<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotificationPreference extends Model
{
    use HasFactory;

    protected  $fillable = [
        'user_id',
        'key',
        'enabled'
    ];


    public function master()
    {
        return $this->belongsTo(NotificationPreferenceMaster::class, 'key', 'key');
    }
}
