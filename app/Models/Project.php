<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'progress',
        'due_date',
        'priority',
    ];

    protected $casts = [
        'due_date' => 'date',
        'progress' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 🔗 TAMBAHKAN RELATIONSHIP INI
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // Helper methods
    public function getTaskStats()
    {
        return [
            'total' => $this->tasks()->count(),
            'completed' => $this->tasks()->where('status', 'Completed')->count(),
            'in_progress' => $this->tasks()->where('status', 'In Progress')->count(),
            'todo' => $this->tasks()->where('status', 'Todo')->count(),
        ];
    }

    // Calculate progress based on completed tasks
    public function updateProgress()
    {
        $totalTasks = $this->tasks()->count();
        
        if ($totalTasks === 0) {
            $this->update(['progress' => 0]);
            return;
        }

        $completedTasks = $this->tasks()->where('status', 'Completed')->count();
        $newProgress = round(($completedTasks / $totalTasks) * 100);

        $this->update(['progress' => $newProgress]);
    }
}
