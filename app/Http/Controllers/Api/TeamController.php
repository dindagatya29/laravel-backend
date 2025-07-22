<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Department;


class TeamController extends Controller
{
    /**
     * Get all team members
     */
    public function index()
    {
        try {
            $teamMembers = User::select([
                'id', 'name', 'email', 'role', 'department', 'avatar', 'color',
                'status', 'join_date', 'last_active', 'projects', 'skills',
                'tasks_completed', 'tasks_in_progress', 'performance', 'bio', 'phone'
            ])->get();

            // Transform data
            $transformedMembers = $teamMembers->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->role,
                    'department' => $member->department,
                    'avatar' => $member->avatar ?: '/placeholder.svg?height=40&width=40',
                    'color' => $member->color,
                    'status' => $member->status,
                    'joinDate' => $member->join_date,
                    'lastActive' => $member->last_active,
                    'projects' => is_string($member->projects) ? json_decode($member->projects, true) : ($member->projects ?: []),
                    'skills' => is_string($member->skills) ? json_decode($member->skills, true) : ($member->skills ?: []),
                    'tasksCompleted' => $member->tasks_completed,
                    'tasksInProgress' => $member->tasks_in_progress,
                    'performance' => $member->performance,
                    'bio' => $member->bio,
                    'phone' => $member->phone
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Team members retrieved successfully',
                'data' => $transformedMembers,
                'count' => $transformedMembers->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve team members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get team statistics
     */
    public function stats()
    {
        try {
            $totalMembers = User::count();
            $onlineMembers = User::where('status', 'online')->count();
            $departments = User::distinct('department')->whereNotNull('department')->count();
            $avgPerformance = User::avg('performance') ?: 0;

            $statusDistribution = [
                'online' => User::where('status', 'online')->count(),
                'away' => User::where('status', 'away')->count(),
                'offline' => User::where('status', 'offline')->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Team statistics retrieved successfully',
                'data' => [
                    'totalMembers' => $totalMembers,
                    'onlineMembers' => $onlineMembers,
                    'departments' => $departments,
                    'avgPerformance' => round($avgPerformance, 1),
                    'statusDistribution' => $statusDistribution
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve team statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get departments
     */
    public function departments()
{
    try {
        $departments = DB::table('users')
            ->select('department', DB::raw('count(*) as count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->get()
            ->map(function ($dept) {
                return [
                    'name' => $dept->department,
                    'count' => $dept->count,
                    'color' => $this->getDepartmentColor($dept->department),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $departments,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}


    public function createDepartment(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'color' => 'nullable|string',
    ]);

    // Simpan ke tabel departments misalnya
    $department = Department::create([
        'name' => $validated['name'],
        'color' => $validated['color'] ?? '#999999',
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Department created successfully',
        'data' => $department
    ]);
}
    /**
     * Get team activity
     */
    public function activity()
    {
        try {
            // Generate sample activity data
            $activities = collect([
                [
                    'id' => 1,
                    'user' => 'John Smith',
                    'action' => 'completed task',
                    'target' => 'User Authentication',
                    'project' => 'Web App',
                    'time' => '2 hours ago',
                    'avatar' => '/placeholder.svg?height=32&width=32',
                    'color' => '#3B82F6'
                ],
                [
                    'id' => 2,
                    'user' => 'Sarah Johnson',
                    'action' => 'created project',
                    'target' => 'Mobile App Redesign',
                    'project' => 'Design System',
                    'time' => '4 hours ago',
                    'avatar' => '/placeholder.svg?height=32&width=32',
                    'color' => '#10B981'
                ],
                [
                    'id' => 3,
                    'user' => 'Mike Chen',
                    'action' => 'updated status',
                    'target' => 'API Integration',
                    'project' => 'Backend',
                    'time' => '6 hours ago',
                    'avatar' => '/placeholder.svg?height=32&width=32',
                    'color' => '#F59E0B'
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Team activity retrieved successfully',
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve team activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single team member
     */
    public function show($id)
    {
        try {
            $member = User::findOrFail($id);

            $transformedMember = [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role,
                'department' => $member->department,
                'avatar' => $member->avatar ?: '/placeholder.svg?height=40&width=40',
                'color' => $member->color,
                'status' => $member->status,
                'joinDate' => $member->join_date,
                'lastActive' => $member->last_active,
                'projects' => is_string($member->projects) ? json_decode($member->projects, true) : ($member->projects ?: []),
                'skills' => is_string($member->skills) ? json_decode($member->skills, true) : ($member->skills ?: []),
                'tasksCompleted' => $member->tasks_completed,
                'tasksInProgress' => $member->tasks_in_progress,
                'performance' => $member->performance,
                'bio' => $member->bio,
                'phone' => $member->phone
            ];

            return response()->json([
                'success' => true,
                'message' => 'Team member retrieved successfully',
                'data' => $transformedMember
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Team member not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create new team member
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'role' => 'required|string|max:255',
                'department' => 'required|string|max:255',
                'skills' => 'nullable|array',
                'bio' => 'nullable|string',
                'phone' => 'nullable|string|max:20'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $member = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt('password123'), // Default password
                'role' => $request->role,
                'department' => $request->department,
                'avatar' => '/placeholder.svg?height=40&width=40',
                'color' => $this->getRandomColor(),
                'status' => 'offline',
                'join_date' => now(),
                'skills' => $request->skills ? json_encode($request->skills) : null,
                'bio' => $request->bio,
                'phone' => $request->phone,
                'performance' => rand(85, 98)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Team member created successfully',
                'data' => $member
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create team member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update team member
     */
    public function update(Request $request, $id)
    {
        try {
            $member = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'role' => 'sometimes|string|max:255',
                'department' => 'sometimes|string|max:255',
                'status' => 'sometimes|in:online,away,offline',
                'skills' => 'nullable|array',
                'bio' => 'nullable|string',
                'phone' => 'nullable|string|max:20'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only([
                'name', 'email', 'role', 'department', 'status', 'bio', 'phone'
            ]);

            if ($request->has('skills')) {
                $updateData['skills'] = json_encode($request->skills);
            }

            $member->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Team member updated successfully',
                'data' => $member
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update team member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete team member
     */
    public function destroy($id)
    {
        try {
            $member = User::findOrFail($id);
            $member->delete();

            return response()->json([
                'success' => true,
                'message' => 'Team member deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete team member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Get department color
     */
    private function getDepartmentColor($department)
    {
        $colors = [
            'Engineering' => '#3B82F6',
            'Design' => '#10B981',
            'Marketing' => '#F59E0B',
            'Management' => '#EF4444',
            'Analytics' => '#8B5CF6'
        ];

        return $colors[$department] ?? '#6B7280';
    }

    /**
     * Helper: Get random color
     */
    private function getRandomColor()
    {
        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6'];
        return $colors[array_rand($colors)];
    }
}
