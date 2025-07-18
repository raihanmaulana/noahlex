<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type_id',
        'location',
        'date',
        'status_id',
        'size',
        'project_manager_id',
        'enable_workflow',
        'userId',
        'userUpdateId',
        'isDeleted',
        'deletedBy',
        'deletedAt',
    ];

    public function type()
    {
        return $this->belongsTo(ProjectType::class, 'type_id');
    }

    public function status()
    {
        return $this->belongsTo(ProjectStatus::class, 'status_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }
}
