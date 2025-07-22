<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kpi extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'name',
        'description',
        'category',
        'unit',
        'target_value',
        'current_value',
        'baseline_value',
        'frequency',
        'direction',
        'status',
        'start_date',
        'end_date',
        'metadata'
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'baseline_value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array'
    ];

    /**
     * Get the user that owns the KPI
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project that owns the KPI
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope for active KPIs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for KPIs by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for KPIs by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for KPIs by project
     */
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Get progress percentage
     */
    public function getProgressAttribute()
    {
        if ($this->target_value == 0) {
            return 0;
        }

        $progress = (($this->current_value - $this->baseline_value) / ($this->target_value - $this->baseline_value)) * 100;
        
        return min(max($progress, 0), 100);
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'active':
                return 'green';
            case 'paused':
                return 'yellow';
            case 'completed':
                return 'blue';
            case 'cancelled':
                return 'red';
            default:
                return 'gray';
        }
    }

    /**
     * Get direction icon
     */
    public function getDirectionIconAttribute()
    {
        switch ($this->direction) {
            case 'increase':
                return '↗️';
            case 'decrease':
                return '↘️';
            case 'maintain':
                return '→';
            default:
                return '→';
        }
    }

    /**
     * Get frequency label
     */
    public function getFrequencyLabelAttribute()
    {
        switch ($this->frequency) {
            case 'daily':
                return 'Daily';
            case 'weekly':
                return 'Weekly';
            case 'monthly':
                return 'Monthly';
            case 'quarterly':
                return 'Quarterly';
            case 'yearly':
                return 'Yearly';
            default:
                return 'Monthly';
        }
    }

    /**
     * Check if KPI is on track
     */
    public function isOnTrack()
    {
        $progress = $this->progress;
        $daysElapsed = now()->diffInDays($this->start_date);
        $totalDays = $this->end_date ? now()->diffInDays($this->end_date) + $daysElapsed : 30;
        $expectedProgress = ($daysElapsed / $totalDays) * 100;

        return $progress >= $expectedProgress;
    }

    /**
     * Update current value
     */
    public function updateCurrentValue($value)
    {
        $this->update(['current_value' => $value]);
        return $this;
    }
}
