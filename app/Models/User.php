<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
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


// public function hasPermission(string $permissionName): bool
// {
//     if ($this->role === 'admin') {
//         // Admin punya semua izin
//         return true;
//     }

//     $permission = DB::table('permissions')->where('name', $permissionName)->first();

//     if (!$permission) {
//         return false;
//     }

//     return DB::table('role_permissions')
//         ->where('role', $this->role)
//         ->where('permission_id', $permission->id)
//         ->where('allowed', true)
//         ->exists();
// }


// public function getAllPermissions()
// {
//     if ($this->role === 'admin') {
//         return DB::table('permissions')->pluck('name');
//     }

//     return DB::table('role_permissions')
//         ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
//         ->where('role_permissions.role', $this->role)
//         ->where('role_permissions.allowed', true)
//         ->pluck('permissions.name');
// }


}
