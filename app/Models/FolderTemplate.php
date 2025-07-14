<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolderTemplate extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'path',
        'sort_order',
        'userId',
        'userUpdateId',
        'isDeleted',
        'deletedBy',
        'deletedAt',
    ];

    public function children()
    {
        return $this->hasMany(FolderTemplate::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(FolderTemplate::class, 'parent_id');
    }
}
