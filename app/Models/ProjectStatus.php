<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'userId',
        'userUpdateId',
        'isDeleted',
        'deletedBy',
        'deletedAt',
    ];
}
