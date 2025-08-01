<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMetaData extends Model
{
    protected $fillable = [
        'project_id',
        'document_type',
        'revision_limit',
        'discipline',
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

    public function disciplineUser()
    {
        return $this->belongsTo(User::class, 'discipline');
    }
}
