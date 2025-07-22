<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Notification;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notifications = [
            [
                'user_id' => null,
                'user_name' => 'John Doe',
                'action' => 'created task',
                'target' => 'UI Design Implementation',
                'type' => 'task',
                'details' => 'New task created for UI design phase with deadline next week',
                'priority' => 'high',
                'read' => false,
                'metadata' => ['task_id' => 1, 'project_id' => 1],
                'created_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subHours(2)
            ],
            [
                'user_id' => null,
                'user_name' => 'Sarah Wilson',
                'action' => 'updated project',
                'target' => 'E-commerce Platform',
                'type' => 'project',
                'details' => 'Project progress updated to 75% completion',
                'priority' => 'medium',
                'read' => true,
                'metadata' => ['project_id' => 2, 'progress' => 75],
                'created_at' => Carbon::now()->subHours(3),
                'updated_at' => Carbon::now()->subHours(3)
            ],
            [
                'user_id' => null,
                'user_name' => 'Alex Johnson',
                'action' => 'uploaded file',
                'target' => 'design-mockups.zip',
                'type' => 'file',
                'details' => 'Design mockups uploaded (15.2 MB) for review',
                'priority' => 'low',
                'read' => false,
                'metadata' => ['file_size' => '15.2 MB', 'file_type' => 'zip'],
                'created_at' => Carbon::now()->subHours(4),
                'updated_at' => Carbon::now()->subHours(4)
            ],
            [
                'user_id' => null,
                'user_name' => 'Tom Wilson',
                'action' => 'joined team',
                'target' => 'Development Team',
                'type' => 'team',
                'details' => 'New team member joined as Frontend Developer',
                'priority' => 'medium',
                'read' => true,
                'metadata' => ['team_id' => 1, 'role' => 'Frontend Developer'],
                'created_at' => Carbon::now()->subHours(5),
                'updated_at' => Carbon::now()->subHours(5)
            ],
            [
                'user_id' => null,
                'user_name' => 'System',
                'action' => 'maintenance scheduled',
                'target' => 'Server Maintenance',
                'type' => 'system',
                'details' => 'Scheduled maintenance on Sunday 2:00 AM - 4:00 AM',
                'priority' => 'high',
                'read' => false,
                'metadata' => ['maintenance_type' => 'server', 'duration' => '2 hours'],
                'created_at' => Carbon::now()->subHours(6),
                'updated_at' => Carbon::now()->subHours(6)
            ],
            [
                'user_id' => null,
                'user_name' => 'Maria Garcia',
                'action' => 'commented on task',
                'target' => 'Database Schema Design',
                'type' => 'task',
                'details' => 'Added comment: "Please review the new schema changes"',
                'priority' => 'medium',
                'read' => false,
                'metadata' => ['task_id' => 3, 'comment_id' => 1],
                'created_at' => Carbon::now()->subHours(1),
                'updated_at' => Carbon::now()->subHours(1)
            ],
            [
                'user_id' => null,
                'user_name' => 'David Chen',
                'action' => 'completed milestone',
                'target' => 'Phase 1 Development',
                'type' => 'project',
                'details' => 'Successfully completed Phase 1 of the project',
                'priority' => 'high',
                'read' => false,
                'metadata' => ['project_id' => 3, 'milestone_id' => 1],
                'created_at' => Carbon::now()->subMinutes(30),
                'updated_at' => Carbon::now()->subMinutes(30)
            ],
            [
                'user_id' => null,
                'user_name' => 'Lisa Brown',
                'action' => 'shared document',
                'target' => 'Project Requirements.pdf',
                'type' => 'file',
                'details' => 'Shared project requirements document with the team',
                'priority' => 'low',
                'read' => true,
                'metadata' => ['file_id' => 2, 'shared_with' => 'team'],
                'created_at' => Carbon::now()->subHours(8),
                'updated_at' => Carbon::now()->subHours(8)
            ]
        ];

        foreach ($notifications as $notification) {
            Notification::create($notification);
        }
    }
} 