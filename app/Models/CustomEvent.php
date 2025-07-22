<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'date',
        'color',
        'type',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 