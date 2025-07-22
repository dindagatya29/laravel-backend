<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    /**
     * Get application settings
     */
    public function index(): JsonResponse
    {
        try {
            $settings = $this->getSettings();
            
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update application settings
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'companyName' => 'string|max:255',
                'companyEmail' => 'email|max:255',
                'timezone' => 'string|max:50',
                'dateFormat' => 'string|max:20',
                'language' => 'string|max:10',
                'emailNotifications' => 'boolean',
                'pushNotifications' => 'boolean',
                'taskReminders' => 'boolean',
                'projectUpdates' => 'boolean',
                'weeklyReports' => 'boolean',
                'twoFactorAuth' => 'boolean',
                'sessionTimeout' => 'integer|min:5|max:480',
                'passwordExpiry' => 'integer|min:30|max:365',
                'loginAttempts' => 'integer|min:3|max:10',
                'theme' => 'string|in:light,dark,auto',
                'sidebarCollapsed' => 'boolean',
                'compactMode' => 'boolean',
                'slackIntegration' => 'boolean',
                'githubIntegration' => 'boolean',
                'googleCalendar' => 'boolean',
                'jiraIntegration' => 'boolean',
                'autoBackup' => 'boolean',
                'backupFrequency' => 'string|in:daily,weekly,monthly',
                'dataRetention' => 'integer|min:30|max:1095',
                'exportFormat' => 'string|in:csv,json,xlsx,pdf',
            ]);

            // Save settings to cache and file
            $this->saveSettings($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $validated
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export data
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'csv');
            $type = $request->get('type', 'all'); // all, projects, tasks, users
            
            // Validate format
            if (!in_array($format, ['csv', 'json', 'xlsx', 'pdf'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid export format'
                ], 400);
            }
            
            // Generate export data based on type
            $data = $this->generateExportData($type);
            
            // Store export file
            $filename = "nexapro_export_{$type}_{$format}_" . date('Y-m-d_H-i-s') . ".{$format}";
            $filepath = "exports/{$filename}";
            
            Storage::put($filepath, $this->formatExportData($data, $format));
            
            return response()->json([
                'success' => true,
                'message' => 'Export completed successfully',
                'data' => [
                    'filename' => $filename,
                    'download_url' => url("api/admin/settings/download/{$filename}"),
                    'expires_at' => now()->addHours(24)->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download exported file
     */
    public function download($filename): JsonResponse
    {
        try {
            $filepath = "exports/{$filename}";
            
            if (!Storage::exists($filepath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }
            
            $url = Storage::url($filepath);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => $url
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate download link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create backup
     */
    public function backup(): JsonResponse
    {
        try {
            $backupData = [
                'timestamp' => now()->toISOString(),
                'version' => '1.0.0',
                'settings' => $this->getSettings(),
                'statistics' => $this->getSystemStatistics(),
            ];
            
            $filename = "nexapro_backup_" . date('Y-m-d_H-i-s') . ".json";
            $filepath = "backups/{$filename}";
            
            Storage::put($filepath, json_encode($backupData, JSON_PRETTY_PRINT));
            
            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'data' => [
                    'filename' => $filename,
                    'size' => Storage::size($filepath),
                    'created_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->getSystemStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get settings from cache or file
     */
    private function getSettings(): array
    {
        return Cache::remember('app_settings', 3600, function () {
            $defaultSettings = [
                'companyName' => 'NexaPro',
                'companyEmail' => 'admin@nexapro.com',
                'timezone' => 'Asia/Jakarta',
                'dateFormat' => 'DD/MM/YYYY',
                'language' => 'en',
                'emailNotifications' => true,
                'pushNotifications' => true,
                'taskReminders' => true,
                'projectUpdates' => true,
                'weeklyReports' => false,
                'twoFactorAuth' => false,
                'sessionTimeout' => 30,
                'passwordExpiry' => 90,
                'loginAttempts' => 5,
                'theme' => 'light',
                'sidebarCollapsed' => false,
                'compactMode' => false,
                'slackIntegration' => false,
                'githubIntegration' => false,
                'googleCalendar' => false,
                'jiraIntegration' => false,
                'autoBackup' => true,
                'backupFrequency' => 'daily',
                'dataRetention' => 365,
                'exportFormat' => 'csv'
            ];

            if (Storage::exists('settings.json')) {
                $savedSettings = json_decode(Storage::get('settings.json'), true);
                return array_merge($defaultSettings, $savedSettings ?? []);
            }

            return $defaultSettings;
        });
    }

    /**
     * Save settings to cache and file
     */
    private function saveSettings(array $settings): void
    {
        // Save to cache
        Cache::put('app_settings', $settings, 3600);
        
        // Save to file
        Storage::put('settings.json', json_encode($settings, JSON_PRETTY_PRINT));
    }

    /**
     * Generate export data
     */
    private function generateExportData(string $type): array
    {
        switch ($type) {
            case 'projects':
                return \App\Models\Project::with(['tasks'])->get()->toArray();
            case 'tasks':
                return \App\Models\Task::with(['project'])->get()->toArray();
            case 'users':
                return \App\Models\User::all()->toArray();
            default:
                return [
                    'projects' => \App\Models\Project::with(['tasks'])->get()->toArray(),
                    'tasks' => \App\Models\Task::with(['project'])->get()->toArray(),
                    'users' => \App\Models\User::all()->toArray(),
                    'settings' => $this->getSettings(),
                ];
        }
    }

    /**
     * Format export data
     */
    private function formatExportData(array $data, string $format): string
    {
        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            case 'csv':
                return $this->arrayToCsv($data);
            case 'xlsx':
                return $this->arrayToXlsx($data);
            case 'pdf':
                return $this->arrayToPdf($data);
            default:
                return json_encode($data);
        }
    }

    /**
     * Convert array to CSV
     */
    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        // Handle nested data structure
        if (isset($data['projects']) || isset($data['tasks']) || isset($data['users'])) {
            $csv = '';
            foreach ($data as $type => $items) {
                if (!empty($items)) {
                    $csv .= "\n=== $type ===\n";
                    $csv .= $this->arrayToCsv($items);
                }
            }
            return $csv;
        }

        $output = fopen('php://temp', 'r+');
        
        // Write headers
        if (!empty($data) && is_array($data[0])) {
            fputcsv($output, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Convert array to XLSX (simplified)
     */
    private function arrayToXlsx(array $data): string
    {
        // For simplicity, return JSON for now
        // In production, use a library like PhpSpreadsheet
        return json_encode($data);
    }

    /**
     * Convert array to PDF (simplified)
     */
    private function arrayToPdf(array $data): string
    {
        // For simplicity, return JSON for now
        // In production, use a library like Dompdf
        return json_encode($data);
    }

    /**
     * Get system statistics
     */
    private function getSystemStatistics(): array
    {
        return [
            'total_users' => \App\Models\User::count(),
            'total_projects' => \App\Models\Project::count(),
            'total_tasks' => \App\Models\Task::count(),
            'completed_tasks' => \App\Models\Task::where('status', 'Completed')->count(),
            'active_projects' => \App\Models\Project::where('status', 'In Progress')->count(),
            'storage_used' => $this->getStorageUsage(),
            'last_backup' => $this->getLastBackupTime(),
            'system_uptime' => $this->getSystemUptime(),
        ];
    }

    /**
     * Get storage usage
     */
    private function getStorageUsage(): array
    {
        $totalSpace = disk_total_space(storage_path());
        $freeSpace = disk_free_space(storage_path());
        $usedSpace = $totalSpace - $freeSpace;
        
        return [
            'total' => $totalSpace,
            'used' => $usedSpace,
            'free' => $freeSpace,
            'percentage' => round(($usedSpace / $totalSpace) * 100, 2)
        ];
    }

    /**
     * Get last backup time
     */
    private function getLastBackupTime(): ?string
    {
        $backups = Storage::files('backups');
        if (empty($backups)) {
            return null;
        }
        
        $latestBackup = end($backups);
        return Storage::lastModified($latestBackup);
    }

    /**
     * Get system uptime
     */
    private function getSystemUptime(): string
    {
        // For simplicity, return current time
        // In production, get actual system uptime
        return now()->toISOString();
    }
} 