<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Okr extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'objective',
        'description',
        'category',
        'type',
        'status',
        'start_date',
        'end_date',
        'metadata'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array'
    ];

    /**
     * Get the user that owns the OKR
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project that owns the OKR
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the key results for this OKR
     */
    public function keyResults(): HasMany
    {
        return $this->hasMany(KeyResult::class);
    }

    /**
     * Scope for active OKRs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for OKRs by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for OKRs by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for OKRs by project
     */
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Get overall progress percentage
     */
    public function getOverallProgressAttribute()
    {
        $keyResults = $this->keyResults()->active()->get();
        
        if ($keyResults->isEmpty()) {
            return 0;
        }

        $totalWeight = $keyResults->sum('weight');
        $weightedProgress = $keyResults->sum(function ($kr) {
            return $kr->progress * $kr->weight;
        });

        return $totalWeight > 0 ? $weightedProgress / $totalWeight : 0;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'active':
                return 'green';
            case 'completed':
                return 'blue';
            case 'cancelled':
                return 'red';
            default:
                return 'gray';
        }
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute()
    {
        switch ($this->type) {
            case 'company':
                return 'Company';
            case 'team':
                return 'Team';
            case 'individual':
                return 'Individual';
            default:
                return 'Individual';
        }
    }

    /**
     * Check if OKR is on track
     */
    public function isOnTrack()
    {
        $progress = $this->overall_progress;
        $daysElapsed = now()->diffInDays($this->start_date);
        $totalDays = $this->end_date ? now()->diffInDays($this->end_date) + $daysElapsed : 90;
        $expectedProgress = ($daysElapsed / $totalDays) * 100;

        return $progress >= $expectedProgress;
    }

    /**
     * Get completion status
     */
    public function getCompletionStatusAttribute()
    {
        $progress = $this->overall_progress;
        
        if ($progress >= 100) {
            return 'completed';
        } elseif ($progress >= 80) {
            return 'on_track';
        } elseif ($progress >= 60) {
            return 'at_risk';
        } else {
            return 'behind';
        }
    }
}
