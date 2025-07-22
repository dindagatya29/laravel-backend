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
        $users = User::select('id', 'name', 'email', 'avatar_url')->get();

        return response()->json([
            'success' => true,
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
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
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
                'projects_count' => $user->projects->count(),
                'created_projects_count' => $user->createdProjects->count(),
                'assigned_tasks_count' => $user->assignedTasks->count(),
            ],
        ]);
    }

    // Get all users with role
    public function usersWithRole(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'role', 'avatar_url')->get();
        return response()->json(['success' => true, 'data' => $users]);
    }

    // Update user role (admin only)
    public function updateRole(Request $request, $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|in:admin,project_manager,member',
        ]);
        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();
        return response()->json(['success' => true, 'message' => 'Role updated', 'data' => $user]);
    }

    // Get all roles
    public function roles(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['admin', 'project_manager', 'member']]);
    }

    // Get all permissions
    public function permissions(): JsonResponse
    {
        $permissions = Permission::all();
        return response()->json(['success' => true, 'data' => $permissions]);
    }

    // Get/set permissions for a role
    public function getRolePermissions($role): JsonResponse
    {
        $perms = RolePermission::where('role', $role)->with('permission')->get();
        return response()->json(['success' => true, 'data' => $perms]);
    }
    public function setRolePermissions(Request $request, $role): JsonResponse
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*.permission_id' => 'required|exists:permissions,id',
            'permissions.*.allowed' => 'required|boolean',
        ]);
        foreach ($request->permissions as $perm) {
            RolePermission::updateOrCreate(
                ['role' => $role, 'permission_id' => $perm['permission_id']],
                ['allowed' => $perm['allowed']]
            );
        }
        return response()->json(['success' => true, 'message' => 'Permissions updated']);
    }

    // Get user permissions by user ID
    public function getUserPermissions($userId): JsonResponse
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Get permissions for user's role
        $rolePermissions = RolePermission::where('role', $user->role)
            ->where('allowed', true)
            ->with('permission')
            ->get();

        $permissions = $rolePermissions->map(function ($rolePerm) {
            return [
                'id' => $rolePerm->permission->id,
                'name' => $rolePerm->permission->name,
                'description' => $rolePerm->permission->description
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }
}
