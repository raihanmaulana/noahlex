<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectDocumentStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color_hex', 'description'];

    public function documents()
    {
        return $this->hasMany(ProjectDocument::class, 'status_id');
    }
    
}
