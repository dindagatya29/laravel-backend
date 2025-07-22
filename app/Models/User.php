<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'status',
        'join_date',
        'last_active',
        'skills',
        'bio',
        'phone',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'join_date' => 'date',
        'last_active' => 'datetime',
        'skills' => 'array',
    ];

    // Relationships
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members', 'user_id', 'project_id');
    }

    // Helper methods
    public function getTaskStats()
    {
        $tasks = $this->assignedTasks();
        
        return [
            'total' => $tasks->count(),
            'completed' => $tasks->where('status', 'Completed')->count(),
            'in_progress' => $tasks->where('status', 'In Progress')->count(),
            'todo' => $tasks->where('status', 'Todo')->count(),
        ];
    }

    public function getPerformanceScore()
    {
        $stats = $this->getTaskStats();
        
        if ($stats['total'] === 0) {
            return 85; // Default score for new users
        }
        
        $completionRate = ($stats['completed'] / $stats['total']) * 100;
        
        // Add bonus for having tasks in progress (shows activity)
        $activityBonus = $stats['in_progress'] > 0 ? 5 : 0;
        
        // Cap at 100%
        return min(100, round($completionRate + $activityBonus));
    }

    public function getProjectNames()
    {
        $createdProjects = $this->createdProjects()->pluck('name')->toArray();
        $memberProjects = $this->projects()->pluck('name')->toArray();
        
        return array_unique(array_merge($createdProjects, $memberProjects));
    }
}
