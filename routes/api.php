<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\KpiController;
use App\Http\Controllers\Api\OkrController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\Api\CustomEventController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TimeEntryController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check routes
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API is working',
        'timestamp' => now(),
    ]);
});

Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 'OK',
            'message' => 'Database connection successful',
            'timestamp' => now(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => 'Database connection failed: ' . $e->getMessage(),
            'timestamp' => now(),
        ], 500);
    }
});

// User routes
Route::apiResource('users', UserController::class);

// Project routes
Route::apiResource('projects', ProjectController::class);
Route::get('projects/stats', [ProjectController::class, 'stats']);
Route::get('projects/{id}/stats', [ProjectController::class, 'stats']);

// Task routes
Route::apiResource('tasks', TaskController::class);
Route::patch('tasks/{id}/status', [TaskController::class, 'updateStatus']);

// Team routes
Route::prefix('team')->group(function () {
    Route::get('/', [TeamController::class, 'index']);
    Route::post('/', [TeamController::class, 'store']);
    Route::get('/stats', [TeamController::class, 'stats']);
    Route::get('/departments', [TeamController::class, 'departments']); // ✅ Fix di sini
    Route::post('/departments', [TeamController::class, 'createDepartment']); // ✅
    Route::get('/activity', [TeamController::class, 'activity']);
    Route::get('/{id}', [TeamController::class, 'show']);
    Route::put('/{id}', [TeamController::class, 'update']);
    Route::delete('/{id}', [TeamController::class, 'destroy']);
});



// Activity Logs
Route::get('/activity-logs', [ActivityLogController::class, 'index']);
Route::post('/activity-logs', [ActivityLogController::class, 'store']);
Route::get('/activity-logs/stats', [ActivityLogController::class, 'stats']);
Route::get('/activity-logs/recent', [ActivityLogController::class, 'recent']);
Route::delete('/activity-logs/clear', [ActivityLogController::class, 'clear']);
Route::delete('/activity-logs/clear-all', [ActivityLogController::class, 'clearAll']);

// Custom Events
Route::get('custom-events', [CustomEventController::class, 'index']);
Route::post('custom-events', [CustomEventController::class, 'store']);

// KPI routes
Route::prefix('kpis')->group(function () {
    Route::get('/', [KpiController::class, 'index']);
    Route::post('/', [KpiController::class, 'store']);
    Route::get('/stats', [KpiController::class, 'stats']);
    Route::get('/categories', [KpiController::class, 'categories']);
    Route::get('/{id}', [KpiController::class, 'show']);
    Route::put('/{id}', [KpiController::class, 'update']);
    Route::delete('/{id}', [KpiController::class, 'destroy']);
    Route::patch('/{id}/value', [KpiController::class, 'updateValue']);
});

// OKR routes
Route::prefix('okrs')->group(function () {
    Route::get('/', [OkrController::class, 'index']);
    Route::post('/', [OkrController::class, 'store']);
    Route::get('/stats', [OkrController::class, 'stats']);
    Route::get('/types', [OkrController::class, 'types']);
    Route::get('/{id}', [OkrController::class, 'show']);
    Route::put('/{id}', [OkrController::class, 'update']);
    Route::delete('/{id}', [OkrController::class, 'destroy']);
    Route::patch('/{okrId}/key-results/{keyResultId}/value', [OkrController::class, 'updateKeyResultValue']);
});

// Tambahkan group middleware web untuk login dan register
// Route::middleware('web')->group(function () {
//     Route::post('login', [AuthController::class, 'login']);
//     Route::post('register', [AuthController::class, 'register']);
// });

// Tambahkan route untuk mengambil user yang sedang login
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->post('/profile', [ProfileController::class, 'update']);

// Route login baru (token-based, tanpa session)
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

// Role & Permission Management
Route::prefix('admin')->group(function () {
    Route::get('users/with-role', [UserController::class, 'usersWithRole']);
    Route::patch('users/{id}/role', [UserController::class, 'updateRole']);
    Route::get('roles', [UserController::class, 'roles']);
    Route::get('permissions', [UserController::class, 'permissions']);
    Route::get('role-permissions/{role}', [UserController::class, 'getRolePermissions']);
    Route::post('role-permissions/{role}', [UserController::class, 'setRolePermissions']);
    Route::get('user-permissions/{userId}', [UserController::class, 'getUserPermissions']);
    
    // Settings routes
    Route::get('settings', [SettingsController::class, 'index']);
    Route::post('settings', [SettingsController::class, 'store']);
    Route::post('settings/export', [SettingsController::class, 'export']);
    Route::get('settings/download/{filename}', [SettingsController::class, 'download']);
    Route::post('settings/backup', [SettingsController::class, 'backup']);
    Route::get('settings/statistics', [SettingsController::class, 'statistics']);
});

// Fallback route
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found'
    ], 404);
});

// Tambahkan route group:
Route::apiResource('time-entries', TimeEntryController::class);
// Pastikan endpoint dokumen sudah ada: upload, hapus, download, preview.

// Kembalikan route files dengan middleware auth:sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('files', FileController::class);
    Route::get('files/{id}/download', [FileController::class, 'download']);
    Route::get('files/{id}/preview', [FileController::class, 'preview']);
});

Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/', [NotificationController::class, 'create']);
    Route::post('/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/delete', [NotificationController::class, 'delete']);
    Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
    Route::get('/stats', [NotificationController::class, 'stats']);
    Route::put('/settings', [NotificationController::class, 'updateSettings']);
});

