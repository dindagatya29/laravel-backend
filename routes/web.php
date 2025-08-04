<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

Route::get('/', function () {
    return response()->json([
        'message' => 'Project Management API',
        'version' => '1.0.0',
        'endpoints' => [
            'health' => '/api/health',
            'projects' => '/api/projects',
            'stats' => '/api/projects/stats'
        ]
    ]);
});

// Test route untuk debugging
Route::get('/test-db', function () {
    try {
        $count = \App\Models\Project::count();
        return response()->json([
            'database_connected' => true,
            'projects_count' => $count,
            'sample_project' => \App\Models\Project::first()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'database_connected' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/download/{filename}', function ($filename) {
    $path = storage_path('app/public/documents/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    return response()->file($path);
});