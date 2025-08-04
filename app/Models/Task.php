<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'project_id',
        'assignee_id',
        'status',
        'priority',
        'due_date',
        'progress',
        'tags',
    ];

    protected $casts = [
        'due_date' => 'date',
        'progress' => 'integer',
        'tags' => 'array', // Cast JSON to array
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    // Scopes untuk filtering
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', '%' . $search . '%')
              ->orWhere('description', 'like', '%' . $search . '%')
              ->orWhereHas('project', function ($projectQuery) use ($search) {
                  $projectQuery->where('name', 'like', '%' . $search . '%');
              })
              ->orWhereHas('assignee', function ($assigneeQuery) use ($search) {
                  $assigneeQuery->where('name', 'like', '%' . $search . '%');
              });
        });
    }
}
