<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Clear existing users
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Akun admin default
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@company.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'department' => 'Management',
            'status' => 'online',
            'join_date' => now(),
        ]);

        $teamMembers = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@company.com',
                'password' => Hash::make('password123'),
                'role' => 'project_manager',
                'department' => 'Management',
                'avatar' => '/placeholder.svg?height=40&width=40',
                'color' => '#3B82F6',
                'status' => 'online',
                'join_date' => '2023-01-15',
                'last_active' => now(),
                'projects' => json_encode(['Web App', 'Mobile App', 'API Development']),
                'skills' => json_encode(['Project Management', 'Agile', 'Scrum', 'Leadership']),
                'tasks_completed' => 45,
                'tasks_in_progress' => 3,
                'performance' => 95,
                'bio' => 'Experienced project manager with 8+ years in software development.',
                'phone' => '+1-555-0101'
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@company.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'department' => 'Engineering',
                'avatar' => '/placeholder.svg?height=40&width=40',
                'color' => '#10B981',
                'status' => 'online',
                'join_date' => '2022-08-20',
                'last_active' => now()->subMinutes(15),
                'projects' => json_encode(['Web App', 'API Development']),
                'skills' => json_encode(['React', 'Node.js', 'TypeScript', 'PostgreSQL']),
                'tasks_completed' => 67,
                'tasks_in_progress' => 5,
                'performance' => 98,
                'bio' => 'Full-stack developer passionate about clean code and user experience.',
                'phone' => '+1-555-0102'
            ],
            [
                'name' => 'Mike Chen',
                'email' => 'mike.chen@company.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'department' => 'Design',
                'avatar' => '/placeholder.svg?height=40&width=40',
                'color' => '#F59E0B',
                'status' => 'away',
                'join_date' => '2023-03-10',
                'last_active' => now()->subHours(2),
                'projects' => json_encode(['Mobile App', 'Design System']),
                'skills' => json_encode(['Figma', 'Adobe XD', 'Prototyping', 'User Research']),
                'tasks_completed' => 34,
                'tasks_in_progress' => 4,
                'performance' => 92,
                'bio' => 'Creative designer focused on intuitive and accessible user interfaces.',
                'phone' => '+1-555-0103'
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@company.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'department' => 'Engineering',
                'avatar' => '/placeholder.svg?height=40&width=40',
                'color' => '#EF4444',
                'status' => 'online',
                'join_date' => '2022-11-05',
                'last_active' => now()->subMinutes(5),
                'projects' => json_encode(['Infrastructure', 'CI/CD Pipeline']),
                'skills' => json_encode(['Docker', 'Kubernetes', 'AWS', 'Jenkins']),
                'tasks_completed' => 52,
                'tasks_in_progress' => 2,
                'performance' => 96,
                'bio' => 'DevOps specialist ensuring reliable and scalable infrastructure.',
                'phone' => '+1-555-0104'
            ],
            [
                'name' => 'Alex Rodriguez',
                'email' => 'alex.rodriguez@company.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'department' => 'Engineering',
                'avatar' => '/placeholder.svg?height=40&width=40',
                'color' => '#8B5CF6',
                'status' => 'offline',
                'join_date' => '2023-05-18',
                'last_active' => now()->subHours(8),
                'projects' => json_encode(['API Development', 'Database Optimization']),
                'skills' => json_encode(['Laravel', 'PHP', 'MySQL', 'Redis']),
                'tasks_completed' => 28,
                'tasks_in_progress' => 6,
                'performance' => 89,
                'bio' => 'Backend developer with expertise in scalable API architecture.',
                'phone' => '+1-555-0105'
            ],
            [
                'name' => 'Lisa Wang',
                'email' => 'lisa.wang@company.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'department' => 'Marketing',
                'avatar' => '/placeholder.svg?height=40&width=40',
                'color' => '#EC4899',
                'status' => 'away',
                'join_date' => '2022-12-01',
                'last_active' => now()->subHours(1),
                'projects' => json_encode(['Product Launch', 'Brand Campaign']),
                'skills' => json_encode(['Digital Marketing', 'SEO', 'Content Strategy', 'Analytics']),
                'tasks_completed' => 41,
                'tasks_in_progress' => 3,
                'performance' => 94,
                'bio' => 'Marketing professional driving growth through data-driven strategies.',
                'phone' => '+1-555-0106'
            ],
            [
                'name' => 'David Brown',
                'email' => 'david.brown@company.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'department' => 'Engineering',
                'avatar' => '/placeholder.svg?height=40&width=40',
                'color' => '#14B8A6',
                'status' => 'online',
                'join_date' => '2023-02-14',
                'last_active' => now()->subMinutes(30),
                'projects' => json_encode(['Web App', 'Mobile App']),
                'skills' => json_encode(['Test Automation', 'Selenium', 'Jest', 'Quality Assurance']),
                'tasks_completed' => 39,
                'tasks_in_progress' => 4,
                'performance' => 91,
                'bio' => 'Quality assurance engineer ensuring robust and bug-free software.',
                'phone' => '+1-555-0107'
            ],
            [
                'name' => 'Anna Thompson',
                'email' => 'anna.thompson@company.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'department' => 'Analytics',
                'avatar' => '/placeholder.svg?height=40&width=40',
                'color' => '#6366F1',
                'status' => 'offline',
                'join_date' => '2023-04-22',
                'last_active' => now()->subHours(12),
                'projects' => json_encode(['Analytics Dashboard', 'Data Pipeline']),
                'skills' => json_encode(['Python', 'SQL', 'Tableau', 'Machine Learning']),
                'tasks_completed' => 31,
                'tasks_in_progress' => 2,
                'performance' => 87,
                'bio' => 'Data analyst transforming raw data into actionable business insights.',
                'phone' => '+1-555-0108'
            ]
        ];

        foreach ($teamMembers as $member) {
            User::create($member);
        }

        $this->command->info('Created ' . count($teamMembers) . ' team members successfully!');
    }
}
