<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();
        $users = User::all();

        if ($projects->isEmpty() || $users->isEmpty()) {
            $this->command->error('❌ Please run ProjectSeeder and UserSeeder first!');
            return;
        }

        // Hapus semua entry yang mengandung 'API Documentation'.
        Task::where('title', 'like', '%API Documentation%')->delete();

        $tasks = [
            [
                'title' => 'Design new landing page',
                'description' => 'Create a modern and responsive landing page design',
                'project_id' => $projects->first()->id,
                'assignee_id' => $users->first()->id,
                'status' => 'In Progress',
                'priority' => 'High',
                'due_date' => '2024-02-15',
                'progress' => 75,
                'tags' => ['design', 'frontend'],
            ],
            [
                'title' => 'User Testing',
                'description' => 'Conduct user testing sessions',
                'project_id' => $projects->first()->id,
                'assignee_id' => $users->skip(2)->first()?->id,
                'status' => 'Completed',
                'priority' => 'Low',
                'due_date' => '2024-02-10',
                'progress' => 100,
                'tags' => ['testing', 'ux'],
            ],
        ];

        foreach ($tasks as $taskData) {
            Task::updateOrCreate(
                ['title' => $taskData['title']],
                $taskData
            );
        }

        $this->command->info('✅ Tasks seeded successfully!');
    }
}