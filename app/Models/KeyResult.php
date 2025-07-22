<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'okr_id',
        'title',
        'description',
        'unit',
        'target_value',
        'current_value',
        'baseline_value',
        'direction',
        'status',
        'weight',
        'metadata'
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'baseline_value' => 'decimal:2',
        'weight' => 'integer',
        'metadata' => 'array'
    ];

    /**
     * Get the OKR that owns the key result
     */
    public function okr(): BelongsTo
    {
        return $this->belongsTo(Okr::class);
    }

    /**
     * Scope for active key results
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for key results by OKR
     */
    public function scopeByOkr($query, $okrId)
    {
        return $query->where('okr_id', $okrId);
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
     * Check if key result is completed
     */
    public function isCompleted()
    {
        return $this->progress >= 100;
    }

    /**
     * Check if key result is on track
     */
    public function isOnTrack()
    {
        return $this->progress >= 80;
    }

    /**
     * Check if key result is at risk
     */
    public function isAtRisk()
    {
        return $this->progress >= 60 && $this->progress < 80;
    }

    /**
     * Check if key result is behind
     */
    public function isBehind()
    {
        return $this->progress < 60;
    }

    /**
     * Update current value
     */
    public function updateCurrentValue($value)
    {
        $this->update(['current_value' => $value]);
        
        // Auto-complete if target is reached
        if ($this->progress >= 100) {
            $this->update(['status' => 'completed']);
        }
        
        return $this;
    }

    /**
     * Get completion status
     */
    public function getCompletionStatusAttribute()
    {
        if ($this->isCompleted()) {
            return 'completed';
        } elseif ($this->isOnTrack()) {
            return 'on_track';
        } elseif ($this->isAtRisk()) {
            return 'at_risk';
        } else {
            return 'behind';
        }
    }
}
