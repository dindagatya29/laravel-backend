<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;


class NotificationController extends Controller
{
    /**
     * Get all notifications
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Notification::query()
                ->select([
                    'id',
                    'user_name as user',
                    'action',
                    'target',
                    'type',
                    'details',
                    'priority',
                    'read',
                    'created_at',
                    'metadata'
                ])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('type') && $request->type !== 'all') {
                $query->byType($request->type);
            }

            if ($request->has('priority') && $request->priority !== 'all') {
                $query->byPriority($request->priority);
            }

            if ($request->has('read') && $request->read !== 'all') {
                if ($request->read === 'read') {
                    $query->read();
                } else {
                    $query->unread();
                }
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('user_name', 'like', "%{$search}%")
                      ->orWhere('action', 'like', "%{$search}%")
                      ->orWhere('target', 'like', "%{$search}%")
                      ->orWhere('details', 'like', "%{$search}%");
                });
            }

            $notifications = $query->get();

            // Format time and add formatted_time attribute
            $notifications = $notifications->map(function($notification) {
                $notification->time = $notification->formatted_time;
                return $notification;
            });

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'message' => 'Notifications retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        try {
            $notificationId = $request->input('id');
            
            $notification = Notification::find($notificationId);
            
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->markAsRead();
            
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            Notification::where('read', false)->update(['read' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            $notificationId = $request->input('id');
            
            $notification = Notification::find($notificationId);
            
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all notifications
     */
    public function clearAll(Request $request): JsonResponse
    {
        try {
            Notification::truncate();
            
            return response()->json([
                'success' => true,
                'message' => 'All notifications cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $total = Notification::count();
            $unread = Notification::unread()->count();
            $highPriority = Notification::highPriority()->count();
            $read = Notification::read()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'unread' => $unread,
                    'highPriority' => $highPriority,
                    'read' => $read
                ],
                'message' => 'Notification statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $settings = $request->validate([
                'email' => 'boolean',
                'push' => 'boolean',
                'tasks' => 'boolean',
                'projects' => 'boolean',
                'files' => 'boolean',
                'team' => 'boolean'
            ]);

            // In a real app, you would save settings to database
            // For now, we'll just return success
            
            return response()->json([
                'success' => true,
                'data' => $settings,
                'message' => 'Notification settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new notification (for testing real-time)
     */
    public function create(Request $request): JsonResponse
{
    try {
        $data = $request->validate([
            'user_name' => 'required|string',
            'action' => 'required|string',
            'target' => 'required|string',
            'type' => 'required|string',
            'details' => 'required|string',
            'priority' => 'string|in:low,medium,high',
            'metadata' => 'array',
            'phone' => 'required|string' // Tambahkan ini
        ]);

        $notification = Notification::createNotification($data);

        if (!empty($data['send_whatsapp']) && $data['send_whatsapp'] === true) {
    $message = "🔔 {$data['user_name']} {$data['action']} {$data['target']}\n📌 {$data['details']}";
    $this->sendWhatsApp($data['phone'], $message);
}


        // Kirim WhatsApp setelah notifikasi berhasil dibuat
        $message = "🔔 {$data['user_name']} {$data['action']} {$data['target']}.\n📌 {$data['details']}";
        $this->sendWhatsApp($data['phone'], $message);

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification created & WhatsApp sent successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create notification: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Send WhatsApp message using Fonnte API
     */
    private function sendWhatsApp($phone, $message)
{
    $response = Http::withHeaders([
        'Authorization' => 'TKmezBuQwsDonyn53b6y' // Ganti dengan token Fonnte kamu
    ])->post('https://api.fonnte.com/send', [
        'target' => $phone,
        'message' => $message,
        'countryCode' => '62', // Untuk Indonesia
    ]);

    return $response->json();
}

} 