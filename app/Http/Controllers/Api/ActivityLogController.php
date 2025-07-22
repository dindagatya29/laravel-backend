<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    /**
     * Get all activity logs with filtering
     */
    public function index(Request $request)
    {
        try {
            $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

            // Filter by type
            if ($request->has('type') && $request->type !== 'all') {
                $query->ofType($request->type);
            }

            // Filter by user
            if ($request->has('user_id')) {
                $query->byUser($request->user_id);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->where('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('created_at', '<=', $request->end_date);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('user_name', 'like', "%{$search}%")
                      ->orWhere('action', 'like', "%{$search}%")
                      ->orWhere('target', 'like', "%{$search}%")
                      ->orWhere('project', 'like', "%{$search}%")
                      ->orWhere('details', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 20);
            $activities = $query->paginate($perPage);

            // Transform data for frontend
            $transformedActivities = $activities->getCollection()->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'user' => $activity->user_name,
                    'avatar' => $activity->user_avatar ?: $activity->user?->avatar ?: '/placeholder.svg?height=32&width=32',
                    'color' => $activity->user_color ?: $activity->user?->color ?: '#3B82F6',
                    'action' => $activity->action,
                    'target' => $activity->target,
                    'project' => $activity->project,
                    'timestamp' => $activity->created_at->toISOString(),
                    'formatted_time' => $activity->formatted_time,
                    'type' => $activity->type,
                    'details' => $activity->details,
                    'metadata' => $activity->metadata
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Activity logs retrieved successfully',
                'data' => $transformedActivities,
                'pagination' => [
                    'current_page' => $activities->currentPage(),
                    'last_page' => $activities->lastPage(),
                    'per_page' => $activities->perPage(),
                    'total' => $activities->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get activity statistics
     */
    public function stats()
    {
        try {
            $stats = [
                'total' => ActivityLog::count(),
                'today' => ActivityLog::whereDate('created_at', today())->count(),
                'this_week' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'by_type' => [
                    'task' => ActivityLog::ofType('task')->count(),
                    'project' => ActivityLog::ofType('project')->count(),
                    'file' => ActivityLog::ofType('file')->count(),
                    'team' => ActivityLog::ofType('team')->count(),
                    'system' => ActivityLog::ofType('system')->count(),
                ],
                'top_users' => ActivityLog::select('user_id', 'user_name')
                    ->selectRaw('COUNT(*) as activity_count')
                    ->groupBy('user_id', 'user_name')
                    ->orderBy('activity_count', 'desc')
                    ->limit(5)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Activity statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new activity log entry
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'action' => 'required|string|max:255',
                'target' => 'required|string|max:255',
                'project' => 'nullable|string|max:255',
                'type' => 'required|in:task,project,file,team,system',
                'details' => 'nullable|string',
                'metadata' => 'nullable|array'
            ]);

            $user = Auth::user();
            
            $activity = ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?: 'System',
                'user_avatar' => $user?->avatar,
                'user_color' => $user?->color,
                'action' => $validated['action'],
                'target' => $validated['target'],
                'project' => $validated['project'],
                'type' => $validated['type'],
                'details' => $validated['details'],
                'metadata' => $validated['metadata'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activity log created successfully',
                'data' => $activity
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create activity log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activities for dashboard
     */
    public function recent()
    {
        try {
            $recentActivities = ActivityLog::with('user')
                ->recent(7)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'user' => $activity->user_name,
                        'avatar' => $activity->user_avatar ?: $activity->user?->avatar ?: '/placeholder.svg?height=32&width=32',
                        'color' => $activity->user_color ?: $activity->user?->color ?: '#3B82F6',
                        'action' => $activity->action,
                        'target' => $activity->target,
                        'project' => $activity->project,
                        'timestamp' => $activity->created_at->toISOString(),
                        'formatted_time' => $activity->formatted_time,
                        'type' => $activity->type,
                        'details' => $activity->details
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Recent activities retrieved successfully',
                'data' => $recentActivities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all activity logs (no time restriction)
     */
    public function clearAll()
    {
        try {
            $deleted = ActivityLog::truncate();

            return response()->json([
                'success' => true,
                'message' => "Cleared all activity logs",
                'deleted_count' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear all activity logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear old activity logs
     */
    public function clear(Request $request)
    {
        try {
            $days = $request->get('days', 30);
            $deleted = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

            return response()->json([
                'success' => true,
                'message' => "Cleared {$deleted} old activity logs",
                'deleted_count' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear activity logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
