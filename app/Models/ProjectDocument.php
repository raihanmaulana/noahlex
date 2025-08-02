<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDocument extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'file_path',
        'status_id',
        'tags',
        'version',
        'uploaded_by',
        'expiry_date',
        'isDeleted',
        'userId',
        'userUpdateId',
        'deletedBy',
        'deletedAt',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function status()
    {
        return $this->belongsTo(ProjectDocumentStatus::class, 'status_id');
    }
}
