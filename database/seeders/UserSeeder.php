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
                'name' => 'project manajer ',
                'email' => 'projectmanajer@company.com',
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
                'name' => 'Member',
                'email' => 'member@company.com',
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
        ];

        foreach ($teamMembers as $member) {
            User::create($member);
        }

        $this->command->info('Created ' . count($teamMembers) . ' team members successfully!');
    }
}
