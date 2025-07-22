<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'target',
        'type',
        'details',
        'priority',
        'read',
        'metadata'
    ];

    protected $casts = [
        'read' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the notification
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('read', true);
    }

    /**
     * Scope for high priority notifications
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    /**
     * Scope for notifications by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for notifications by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Get formatted time attribute
     */
    public function getFormattedTimeAttribute()
    {
        $now = Carbon::now();
        $created = Carbon::parse($this->created_at);
        $diff = $now->diffInHours($created);

        if ($diff < 1) {
            return 'Just now';
        } elseif ($diff < 24) {
            return $diff . ' hours ago';
        } else {
            $days = $now->diffInDays($created);
            return $days . ' days ago';
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update(['read' => true]);
        return $this;
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->update(['read' => false]);
        return $this;
    }

    /**
     * Create a new notification
     */
    public static function createNotification($data)
    {
        return self::create([
            'user_id' => $data['user_id'] ?? null,
            'user_name' => $data['user_name'],
            'action' => $data['action'],
            'target' => $data['target'],
            'type' => $data['type'],
            'details' => $data['details'],
            'priority' => $data['priority'] ?? 'medium',
            'metadata' => $data['metadata'] ?? null
        ]);
    }
}
