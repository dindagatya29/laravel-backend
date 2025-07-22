<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            [
                'name' => 'Website Redesign',
                'description' => 'Redesign the company website with new branding and improved user experience. This includes updating the UI/UX, implementing responsive design, and optimizing for SEO.',
                'status' => 'In Progress',
                'progress' => 75,
                'due_date' => Carbon::now()->addDays(15)->format('Y-m-d'),
                'priority' => 'High',
            ],
            [
                'name' => 'Mobile App Development',
                'description' => 'Develop a cross-platform mobile application for iOS and Android using React Native. The app will include user authentication, real-time notifications, and offline capabilities.',
                'status' => 'Planning',
                'progress' => 25,
                'due_date' => Carbon::now()->addDays(60)->format('Y-m-d'),
                'priority' => 'Medium',
            ],
            [
                'name' => 'Marketing Campaign Q1',
                'description' => 'Launch a comprehensive digital marketing campaign for Q1 including social media advertising, email marketing, and content creation.',
                'status' => 'Completed',
                'progress' => 100,
                'due_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
                'priority' => 'High',
            ],
            [
                'name' => 'Database Migration',
                'description' => 'Migrate legacy database to new cloud infrastructure with improved performance and security. Includes data backup, migration scripts, and testing.',
                'status' => 'On Hold',
                'progress' => 40,
                'due_date' => Carbon::now()->addDays(90)->format('Y-m-d'),
                'priority' => 'Low',
            ],
            [
                'name' => 'API Integration',
                'description' => 'Integrate third-party APIs for enhanced functionality including payment processing, email services, and analytics tracking.',
                'status' => 'In Progress',
                'progress' => 60,
                'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'priority' => 'Medium',
            ],
            [
                'name' => 'Security Audit',
                'description' => 'Comprehensive security audit and vulnerability assessment of all systems. Includes penetration testing and security recommendations.',
                'status' => 'Planning',
                'progress' => 10,
                'due_date' => Carbon::now()->addDays(45)->format('Y-m-d'),
                'priority' => 'High',
            ],
            [
                'name' => 'Customer Support Portal',
                'description' => 'Build a self-service customer support portal with knowledge base, ticket system, and live chat functionality.',
                'status' => 'Planning',
                'progress' => 5,
                'due_date' => Carbon::now()->addDays(120)->format('Y-m-d'),
                'priority' => 'Medium',
            ],
            [
                'name' => 'Performance Optimization',
                'description' => 'Optimize application performance including database queries, caching implementation, and code refactoring.',
                'status' => 'In Progress',
                'progress' => 35,
                'due_date' => Carbon::now()->addDays(20)->format('Y-m-d'),
                'priority' => 'High',
            ],
        ];

        foreach ($projects as $projectData) {
            Project::create($projectData);
        }

        $this->command->info('Projects seeded successfully!');
    }
}
