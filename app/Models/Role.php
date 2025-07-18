<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'isDeleted',
        'userId',
        'userUpdateId',
        'deletedBy',
        'deletedAt',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
