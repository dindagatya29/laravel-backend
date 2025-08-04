<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * Get all tasks with optional filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Task::with(['project', 'assignee']);

            // Apply filters
            if ($request->filled('status') && $request->status !== 'All') {
                $query->where('status', $request->status);
            }

            if ($request->filled('priority') && $request->priority !== 'All') {
                $query->where('priority', $request->priority);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%')
                      ->orWhereHas('project', function ($projectQuery) use ($search) {
                          $projectQuery->where('name', 'like', '%' . $search . '%');
                      })
                      ->orWhereHas('assignee', function ($assigneeQuery) use ($search) {
                          $assigneeQuery->where('name', 'like', '%' . $search . '%');
                      });
                });
            }

            $tasks = $query->orderBy('created_at', 'desc')->get();

            $formattedTasks = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'project' => $task->project ? $task->project->name : null,
                    'project_id' => $task->project_id,
                    'assignee' => $task->assignee ? $task->assignee->name : null,
                    'assignee_id' => $task->assignee_id,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'dueDate' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                    'progress' => $task->progress,
                    'tags' => $task->tags ?: [],
                    'todo_list' => $task->todo_list ?: [], // Include todo_list
                    'created_at' => $task->created_at->toISOString(),
                    'updated_at' => $task->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedTasks,
                'meta' => [
                    'total' => $tasks->count(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Error: Failed to fetch tasks', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tasks',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create a new task
     */
    public function store(Request $request): JsonResponse
{
    try {
        // VALIDASI INPUT
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|min:1',
            'description' => 'nullable|string|max:1000',
            'project_id' => 'required|exists:projects,id',
            'assignee_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:Todo,In Progress,Completed',
            'priority' => 'nullable|in:Low,Medium,High',
            'due_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',

            // ✅ Validasi todo_list array of objects
            'todo_list' => 'nullable|array',
            'todo_list.*.text' => 'required|string|max:255',
            'todo_list.*.checked' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // SIMPAN TASK
        $taskData = [
            'title' => trim($request->title),
            'description' => $request->description ?? '',
            'project_id' => $request->project_id,
            'assignee_id' => $request->assignee_id,
            'status' => $request->status ?? 'Todo',
            'priority' => $request->priority ?? 'Medium',
            'progress' => $request->progress ?? 0,
            'due_date' => $request->due_date ?? null,
            'tags' => $request->tags ?? [],
            'todo_list' => $request->todo_list ?? [], // ✅ disimpan sebagai array JSON
        ];

        $task = Task::create($taskData);
        $task->load(['project', 'assignee']);

        // Update progress project
        $this->updateProjectProgress($task->project_id);

        // RESPONSE DATA
        $responseData = [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'project' => $task->project ? $task->project->name : null,
            'project_id' => $task->project_id,
            'assignee' => $task->assignee ? $task->assignee->name : null,
            'assignee_id' => $task->assignee_id,
            'status' => $task->status,
            'priority' => $task->priority,
            'dueDate' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
            'progress' => $task->progress,
            'tags' => $task->tags ?: [],
            'todo_list' => $task->todo_list ?: [],
            'created_at' => $task->created_at->toISOString(),
            'updated_at' => $task->updated_at->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $responseData,
        ], 201);

    } catch (\Exception $e) {
        Log::error('API Error: Failed to create task', [
            'error' => $e->getMessage(),
            'request_data' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create task',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}

    /**
     * Get task statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => Task::count(),
                'todo' => Task::where('status', 'Todo')->count(),
                'in_progress' => Task::where('status', 'In Progress')->count(),
                'completed' => Task::where('status', 'Completed')->count(),
                'high_priority' => Task::where('priority', 'High')->count(),
                'medium_priority' => Task::where('priority', 'Medium')->count(),
                'low_priority' => Task::where('priority', 'Low')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('API Error: Failed to fetch task stats', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch task statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get projects for dropdown
     */
    public function getProjects(): JsonResponse
    {
        try {
            $projects = Project::select('id', 'name')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $projects,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch projects',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get users for dropdown
     */
    public function getUsers(): JsonResponse
    {
        try {
            $users = User::select('id', 'name', 'email')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get a specific task
     */
    public function show(string $id): JsonResponse
    {
        try {
            $task = Task::with(['project', 'assignee'])->find($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'project' => $task->project ? $task->project->name : null,
                    'project_id' => $task->project_id,
                    'assignee' => $task->assignee ? $task->assignee->name : null,
                    'assignee_id' => $task->assignee_id,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'dueDate' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                    'progress' => $task->progress,
                    'tags' => $task->tags ?: [],
                    'created_at' => $task->created_at->toISOString(),
                    'updated_at' => $task->updated_at->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch task',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update a task
     */
    public function update(Request $request, string $id): JsonResponse
{
    try {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'project_id' => 'sometimes|required|exists:projects,id',
            'assignee_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:Todo,In Progress,Completed',
            'priority' => 'sometimes|in:Low,Medium,High',
            'due_date' => 'nullable|date',
            'progress' => 'sometimes|integer|min:0|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',

            // ✅ Validasi tambahan untuk todo_list
            'todo_list' => 'nullable|array',
            'todo_list.*.text' => 'required|string|max:255',
            'todo_list.*.checked' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // ✅ Simpan todo_list (pastikan model Task punya casts 'todo_list' => 'array')
        if ($request->has('todo_list')) {
            $validated['todo_list'] = $request->input('todo_list');
        }

        $task->update($validated);
        $task->load(['project', 'assignee']);

        // Update project progress after updating task
        $this->updateProjectProgress($task->project_id);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'project' => $task->project ? $task->project->name : null,
                'project_id' => $task->project_id,
                'assignee' => $task->assignee ? $task->assignee->name : null,
                'assignee_id' => $task->assignee_id,
                'status' => $task->status,
                'priority' => $task->priority,
                'dueDate' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                'progress' => $task->progress,
                'tags' => $task->tags ?: [],
                'todo_list' => $task->todo_list ?: [],
                'created_at' => $task->created_at->toISOString(),
                'updated_at' => $task->updated_at->toISOString(),
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update task',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}


    /**
     * Delete a task and update project progress
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $task = Task::with('project')->find($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found',
                ], 404);
        }

        $projectId = $task->project_id;
        $taskTitle = $task->title;
        
        // Delete the task
        $task->delete();

        // Update project progress after task deletion
        $this->updateProjectProgress($projectId);

        Log::info('API Response: Task deleted and project progress updated', [
            'task_title' => $taskTitle,
            'project_id' => $projectId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);

    } catch (\Exception $e) {
        Log::error('API Error: Failed to delete task', [
            'error' => $e->getMessage(), 
            'id' => $id
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete task',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}

/**
 * Update project progress based on completed tasks
 */
private function updateProjectProgress($projectId)
{
    try {
        $project = \App\Models\Project::find($projectId);
        
        if (!$project) {
            return;
        }

        $totalTasks = $project->tasks()->count();
        
        if ($totalTasks === 0) {
            // No tasks, set progress to 0
            $project->update(['progress' => 0]);
            return;
        }

        $completedTasks = $project->tasks()->where('status', 'Completed')->count();
        $newProgress = round(($completedTasks / $totalTasks) * 100);

        // Update project progress and status
        $status = $project->status;
        if ($newProgress === 100) {
            $status = 'Completed';
        } elseif ($newProgress > 0 && $newProgress < 100) {
            $status = 'In Progress';
        } elseif ($newProgress === 0) {
            $status = 'Planning';
        }

        $project->update([
            'progress' => $newProgress,
            'status' => $status
        ]);

        Log::info('Project progress updated', [
            'project_id' => $projectId,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'new_progress' => $newProgress,
            'new_status' => $status
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to update project progress', [
            'project_id' => $projectId,
            'error' => $e->getMessage()
        ]);
    }
}
}
