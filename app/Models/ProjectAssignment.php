<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectAssignment extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'role_id',
        'isDeleted',
        'userId',
        'userUpdateId',
        'deletedBy',
        'deletedAt',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
