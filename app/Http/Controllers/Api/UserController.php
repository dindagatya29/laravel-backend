<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'role', 'avatar_url')->get();

        return response()->json([
            'success' => true,
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar_url' => $user->avatar_url,
                ];
            }),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with(['projects', 'createdProjects', 'assignedTasks'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'role' => $user->role,
                'projects_count' => $user->projects->count(),
                'created_projects_count' => $user->createdProjects->count(),
                'assigned_tasks_count' => $user->assignedTasks->count(),
            ],
        ]);
    }

    public function usersWithRole(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'role', 'avatar_url')->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function updateRole(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,project_manager,member',
        ]);

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->role = $validated['role'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Role updated',
            'data' => $user,
        ]);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['admin', 'project_manager', 'member']
        ]);
    }

    public function permissions(): JsonResponse
    {
        $permissions = Permission::all();

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    public function getRolePermissions($role): JsonResponse
    {
        $permissions = RolePermission::where('role', $role)
            ->with('permission')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    public function setRolePermissions(Request $request, $role): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.permission_id' => 'required|exists:permissions,id',
            'permissions.*.allowed' => 'required|boolean',
        ]);

        foreach ($validated['permissions'] as $perm) {
            RolePermission::updateOrCreate(
                [
                    'role' => $role,
                    'permission_id' => $perm['permission_id']
                ],
                [
                    'allowed' => $perm['allowed']
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated'
        ]);
    }

    public function getUserPermissions($userId): JsonResponse
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $permissions = RolePermission::where('role', $user->role)
            ->where('allowed', true)
            ->with('permission')
            ->get()
            ->map(function ($perm) {
                return [
                    'id' => $perm->permission->id,
                    'name' => $perm->permission->name,
                    'description' => $perm->permission->description,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }
}
