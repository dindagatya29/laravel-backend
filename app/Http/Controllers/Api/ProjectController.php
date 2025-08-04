<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * Get all projects with optional filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('API Request: Get Projects', [
                'filters' => $request->all(),
                'ip' => $request->ip()
            ]);

            $query = Project::query();

            // Apply filters
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('priority') && $request->priority !== 'all') {
                $query->where('priority', $request->priority);
            }

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            $projects = $query->with('team')->orderBy('created_at', 'desc')->get();



            $formattedProjects = $projects->map(function ($project) {
                // Get real-time task statistics
                $totalTasks = $project->tasks()->count();
                $completedTasks = $project->tasks()->where('status', 'Completed')->count();
                $inProgressTasks = $project->tasks()->where('status', 'In Progress')->count();
                $todoTasks = $project->tasks()->where('status', 'Todo')->count();

                // 🔄 Calculate progress based on tasks instead of using stored progress
                $calculatedProgress = 0;
                if ($totalTasks > 0) {
                    $calculatedProgress = round(($completedTasks / $totalTasks) * 100);
                }

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'status' => $project->status,
                    'progress' => $calculatedProgress, // Use calculated progress
                    'due_date' => $project->due_date ? $project->due_date->format('Y-m-d') : null,
                    'priority' => $project->priority,
                    'created_at' => $project->created_at->toISOString(),
                    'updated_at' => $project->updated_at->toISOString(),
                    'is_overdue' => $project->due_date && $project->due_date->isPast() && $project->status !== 'Completed',
                    'team' => $project->team->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->pivot->role ?? 'Member',
                        ];
                    })->values(), // 👈 tambahkan ini
                    'tasks' => [
                        'total' => $totalTasks,
                        'completed' => $completedTasks,
                        'in_progress' => $inProgressTasks,
                        'todo' => $todoTasks,
                    ],
                ];
            });

            Log::info('API Response: Projects fetched successfully', [
                'count' => $projects->count(),
                'filters_applied' => $request->only(['status', 'priority', 'search'])
            ]);

            return response()->json([
                'success' => true,
                'data' => $formattedProjects,
                'meta' => [
                    'total' => $projects->count(),
                    'filters_applied' => $request->only(['status', 'priority', 'search'])
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: Failed to fetch projects', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch projects',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create a new project
     */
    public function store(Request $request): JsonResponse
{
    try {
        Log::info('API Request: Create Project', [
            'data' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|min:1',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:Planning,In Progress,Completed,On Hold',
            'priority' => 'nullable|in:Low,Medium,High',
            'due_date' => 'nullable|date|after_or_equal:today',
            'progress' => 'nullable|integer|min:0|max:100',
            'team' => 'array', // ✅ Validasi tim sebagai array
            'team.*.id' => 'required|exists:users,id', // ✅ Validasi setiap anggota tim
            'team.*.role' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $projectData = [
            'name' => trim($request->name),
            'description' => $request->description ?? '',
            'status' => $request->status ?? 'Planning',
            'priority' => $request->priority ?? 'Medium',
            'progress' => $request->progress ?? 0,
            'due_date' => $request->due_date ?? null,
        ];

        DB::beginTransaction();

        $project = Project::create($projectData);

        // // ✅ Simpan anggota tim (attach ke pivot table)
        // if ($request->has('team')) {
        //     $teamData = collect($request->team)->mapWithKeys(function ($member) {
        //         return [$member['id'] => ['role' => $member['role'] ?? 'Member']];
        //     })->toArray();

        //     $project->team()->sync($teamData);
        // }

        if ($request->has('team')) {
    $teamMembers = collect($request->team)->mapWithKeys(function ($user) {
        return [$user['id'] => ['role' => $user['role'] ?? 'Member']];
    });
    $project->team()->sync($teamMembers);
}

if ($request->has('team_ids')) {
    $teamMembers = collect($request->team_ids)->mapWithKeys(fn($id) => [$id => ['role' => 'Member']]);
    $project->team()->sync($teamMembers);
}

        DB::commit();

        // Ambil ulang data lengkap
        $project->load('team');

        $taskStats = $project->getTaskStats();

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'progress' => $project->progress,
                'due_date' => optional($project->due_date)->format('Y-m-d'),
                'priority' => $project->priority,
                'created_at' => $project->created_at->toISOString(),
                'updated_at' => $project->updated_at->toISOString(),
                'team' => $project->team->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->pivot->role ?? 'Member',
                    ];
                })->values(), // ✅ Pastikan hasilnya array
                'tasks' => $taskStats,
            ]
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to create project', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create project',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}


    /**
     * Get project statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => Project::count(),
                'planning' => Project::where('status', 'Planning')->count(),
                'in_progress' => Project::where('status', 'In Progress')->count(),
                'completed' => Project::where('status', 'Completed')->count(),
                'on_hold' => Project::where('status', 'On Hold')->count(),
                'high_priority' => Project::where('priority', 'High')->count(),
                'medium_priority' => Project::where('priority', 'Medium')->count(),
                'low_priority' => Project::where('priority', 'Low')->count(),
            ];

            Log::info('API Response: Stats fetched successfully', $stats);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: Failed to fetch stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get a specific project
     */
    public function show(string $id): JsonResponse
    {
        try {
            $project = Project::find($id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found',
                ], 404);
            }

            // Get real-time task statistics
            $totalTasks = $project->tasks()->count();
            $completedTasks = $project->tasks()->where('status', 'Completed')->count();
            $inProgressTasks = $project->tasks()->where('status', 'In Progress')->count();
            $todoTasks = $project->tasks()->where('status', 'Todo')->count();

            // 🔄 Calculate progress based on tasks
            $calculatedProgress = 0;
            if ($totalTasks > 0) {
                $calculatedProgress = round(($completedTasks / $totalTasks) * 100);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'status' => $project->status,
                    'progress' => $calculatedProgress, // Use calculated progress
                    'due_date' => $project->due_date ? $project->due_date->format('Y-m-d') : null,
                    'priority' => $project->priority,
                    'created_at' => $project->created_at->toISOString(),
                    'updated_at' => $project->updated_at->toISOString(),
                    'team' => $project->team->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->pivot->role ?? 'Member',
                        ];
                    })->values(), // 👈 tambahkan ini
                    'tasks' => [
                        'total' => $totalTasks,
                        'completed' => $completedTasks,
                        'in_progress' => $inProgressTasks,
                        'todo' => $todoTasks,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: Failed to fetch project', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch project',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update a project
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $project = Project::find($id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'status' => 'sometimes|in:Planning,In Progress,Completed,On Hold',
                'priority' => 'sometimes|in:Low,Medium,High',
                'due_date' => 'nullable|date',
                'progress' => 'sometimes|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $project->update($validator->validated());

            // 🔄 Recalculate progress based on tasks
            $project->updateProgress();

            // Get updated project with fresh data
            $project->refresh();

            // Get real-time task statistics
            $totalTasks = $project->tasks()->count();
            $completedTasks = $project->tasks()->where('status', 'Completed')->count();
            $inProgressTasks = $project->tasks()->where('status', 'In Progress')->count();
            $todoTasks = $project->tasks()->where('status', 'Todo')->count();

            Log::info('API Response: Project updated', [
                'project_id' => $project->id,
                'new_progress' => $project->progress,
                'tasks_stats' => [
                    'total' => $totalTasks,
                    'completed' => $completedTasks,
                    'in_progress' => $inProgressTasks,
                    'todo' => $todoTasks,
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully',
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'status' => $project->status,
                    'progress' => $project->progress,
                    'due_date' => $project->due_date ? $project->due_date->format('Y-m-d') : null,
                    'priority' => $project->priority,
                    'created_at' => $project->created_at->toISOString(),
                    'updated_at' => $project->updated_at->toISOString(),
                    'team' => $project->team->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->pivot->role ?? 'Member',
                        ];
                    })->values(), // 👈 tambahkan ini
                    'tasks' => [
                        'total' => $totalTasks,
                        'completed' => $completedTasks,
                        'in_progress' => $inProgressTasks,
                        'todo' => $todoTasks,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: Failed to update project', [
                'error' => $e->getMessage(),
                'id' => $id,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update project',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a project
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $project = Project::find($id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found',
                ], 404);
            }

            $projectName = $project->name;
            $project->delete();

            Log::info('API Response: Project deleted', ['project_name' => $projectName]);

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: Failed to delete project', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
