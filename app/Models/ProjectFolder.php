<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFolder extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'parent_id',
        'path',
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

    public function parent()
    {
        return $this->belongsTo(ProjectFolder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProjectFolder::class, 'parent_id');
    }
}
