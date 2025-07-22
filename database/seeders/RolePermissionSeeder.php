<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Get all permissions
        $permissions = DB::table('permissions')->get();
        
        // Define role permissions
        $rolePermissions = [
            'admin' => [
                'manage_users', 'manage_projects', 'manage_tasks', 'view_projects',
                'view_reports', 'export_data', 'manage_settings', 'manage_integrations',
                'manage_roles', 'track_time', 'manage_team', 'assign_tasks',
                'comment_tasks', 'upload_files', 'view_own_reports', 'view_time_tracking'
            ],
            'project_manager' => [
                'manage_projects', 'manage_tasks', 'view_projects', 'view_reports',
                'export_data', 'manage_team', 'track_time', 'assign_tasks',
                'comment_tasks', 'upload_files', 'view_own_reports', 'view_time_tracking'
            ],
            'member' => [
                'view_projects', 'manage_own_tasks', 'comment_tasks', 'upload_files',
                'track_time', 'view_own_reports'
            ]
        ];

        // Clear existing role permissions
        DB::table('role_permissions')->truncate();

        // Insert role permissions
        foreach ($rolePermissions as $role => $permissionNames) {
            foreach ($permissionNames as $permissionName) {
                $permission = $permissions->where('name', $permissionName)->first();
                if ($permission) {
                    DB::table('role_permissions')->insert([
                        'role' => $role,
                        'permission_id' => $permission->id,
                        'allowed' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
} 