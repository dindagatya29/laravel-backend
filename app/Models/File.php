<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'path',
        'type',
        'size',
        'uploaded_by',
        'uploaded_at',
        'tags',
        'project_id',
        'task_id',
        'version',
        'is_latest',
        'folder_id', // Tambahkan ini untuk relasi dengan folder
    ];

    protected $casts = [
        'tags' => 'array',
        'uploaded_at' => 'datetime',
        'is_latest' => 'boolean',
    ];
    
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }
} 