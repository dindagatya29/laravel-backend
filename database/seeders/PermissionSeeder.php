<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'manage_users', 'description' => 'Manage all users'],
            ['name' => 'manage_projects', 'description' => 'Create, edit, delete projects'],
            ['name' => 'manage_tasks', 'description' => 'Create, edit, delete tasks'],
            ['name' => 'manage_own_tasks', 'description' => 'Manage own assigned tasks'],
            ['name' => 'view_projects', 'description' => 'View project details'],
            ['name' => 'view_reports', 'description' => 'View reports and analytics'],
            ['name' => 'export_data', 'description' => 'Export project or report data'],
            ['name' => 'manage_settings', 'description' => 'Change system settings'],
            ['name' => 'manage_integrations', 'description' => 'Manage third-party integrations'],
            ['name' => 'manage_roles', 'description' => 'Manage user roles and permissions'],
            ['name' => 'track_time', 'description' => 'Track and log time entries'],
            ['name' => 'manage_team', 'description' => 'Manage team members'],
            ['name' => 'assign_tasks', 'description' => 'Assign tasks to team members'],
            ['name' => 'comment_tasks', 'description' => 'Add comments to tasks'],
            ['name' => 'upload_files', 'description' => 'Upload files to projects'],
            ['name' => 'view_own_reports', 'description' => 'View own task reports'],
            ['name' => 'view_time_tracking', 'description' => 'View time tracking data'],

            // // ✅ Tambahan khusus document/file access/folder
            // ['name' => 'upload_documents', 'description' => 'Upload document files'],
            // ['name' => 'view_documents', 'description' => 'View list of documents'],
            // ['name' => 'delete_documents', 'description' => 'Delete documents'],
            // ['name' => 'edit_documents', 'description' => 'Edit document details'],
            // ['name' => 'preview_documents', 'description' => 'Preview document files'],
            // ['name' => 'download_documents', 'description' => 'Download documents'],
            // ['name' => 'create_folders', 'description' => 'Create new folders for organizing documents'],
            // ['name' => 'rename_folders', 'description' => 'Rename existing folders'],
            // ['name' => 'delete_folders', 'description' => 'Delete folders and their contents'],
            // ['name' => 'view_folders', 'description' => 'View list of folders'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission['name'],
                'description' => $permission['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
