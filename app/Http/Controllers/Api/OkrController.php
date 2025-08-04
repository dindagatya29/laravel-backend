<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Okr;
use App\Models\KeyResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OkrController extends Controller
{
    /**
     * Get all OKRs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Okr::with(['user', 'project', 'keyResults'])->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            // Filter by user
            if ($request->has('user_id')) {
                $query->byUser($request->user_id);
            }

            // Filter by project
            if ($request->has('project_id')) {
                $query->byProject($request->project_id);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('objective', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 20);
            $okrs = $query->paginate($perPage);

            // Transform data
            $transformedOkrs = $okrs->getCollection()->map(function ($okr) {
                return [
                    'id' => $okr->id,
                    'objective' => $okr->objective,
                    'description' => $okr->description,
                    'category' => $okr->category,
                    'type' => $okr->type,
                    'status' => $okr->status,
                    'start_date' => $okr->start_date->format('Y-m-d'),
                    'end_date' => $okr->end_date?->format('Y-m-d'),
                    'overall_progress' => $okr->overall_progress,
                    'status_color' => $okr->status_color,
                    'type_label' => $okr->type_label,
                    'is_on_track' => $okr->isOnTrack(),
                    'completion_status' => $okr->completion_status,
                    'key_results' => $okr->keyResults->map(function ($kr) {
                        return [
                            'id' => $kr->id,
                            'title' => $kr->title,
                            'description' => $kr->description,
                            'unit' => $kr->unit,
                            'target_value' => $kr->target_value,
                            'current_value' => $kr->current_value,
                            'baseline_value' => $kr->baseline_value,
                            'progress' => $kr->progress,
                            'direction' => $kr->direction,
                            'status' => $kr->status,
                            'weight' => $kr->weight,
                            'status_color' => $kr->status_color,
                            'direction_icon' => $kr->direction_icon,
                            'completion_status' => $kr->completion_status
                        ];
                    }),
                    'user' => $okr->user ? [
                        'id' => $okr->user->id,
                        'name' => $okr->user->name,
                        'avatar' => $okr->user->avatar
                    ] : null,
                    'project' => $okr->project ? [
                        'id' => $okr->project->id,
                        'name' => $okr->project->name
                    ] : null,
                    'created_at' => $okr->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $okr->updated_at->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'OKRs retrieved successfully',
                'data' => $transformedOkrs,
                'pagination' => [
                    'current_page' => $okrs->currentPage(),
                    'last_page' => $okrs->lastPage(),
                    'per_page' => $okrs->perPage(),
                    'total' => $okrs->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve OKRs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get OKR statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => Okr::count(),
                'active' => Okr::active()->count(),
                'completed' => Okr::where('status', 'completed')->count(),
                'on_track' => Okr::active()->get()->filter(fn($okr) => $okr->isOnTrack())->count(),
                'at_risk' => Okr::active()->get()->filter(fn($okr) => !$okr->isOnTrack())->count(),
                'by_type' => Okr::select('type')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'by_status' => Okr::select('status')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
            ];

            return response()->json([
                'success' => true,
                'message' => 'OKR statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve OKR statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new OKR
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'objective' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'required|string|max:255',
                'type' => 'required|in:company,team,individual',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
                'project_id' => 'nullable|exists:projects,id',
                'key_results' => 'required|array|min:1',
                'key_results.*.title' => 'required|string|max:255',
                'key_results.*.description' => 'nullable|string',
                'key_results.*.unit' => 'required|string|max:255',
                'key_results.*.target_value' => 'required|numeric|min:0',
                'key_results.*.current_value' => 'nullable|numeric|min:0',
                'key_results.*.baseline_value' => 'nullable|numeric|min:0',
                'key_results.*.direction' => 'required|in:increase,decrease,maintain',
                'key_results.*.weight' => 'nullable|integer|min:1|max:10'
            ]);

            // Get user from request or use default user
            $user = null;
            if ($request->hasHeader('Authorization')) {
                $token = str_replace('Bearer ', '', $request->header('Authorization'));
                // For now, use the first user as default
                $user = \App\Models\User::first();
            } else {
                $user = \App\Models\User::first();
            }
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            DB::beginTransaction();
            
            $okr = Okr::create([
                'user_id' => $user->id,
                'project_id' => $validated['project_id'] ?? null,
                'objective' => $validated['objective'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'type' => $validated['type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date']
            ]);

            // Create key results
            foreach ($validated['key_results'] as $krData) {
                KeyResult::create([
                    'okr_id' => $okr->id,
                    'title' => $krData['title'],
                    'description' => $krData['description'] ?? null,
                    'unit' => $krData['unit'],
                    'target_value' => $krData['target_value'],
                    'current_value' => $krData['current_value'] ?? 0,
                    'baseline_value' => $krData['baseline_value'] ?? 0,
                    'direction' => $krData['direction'],
                    'weight' => $krData['weight'] ?? 1
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'OKR created successfully',
                'data' => $okr->load(['user', 'project', 'keyResults'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create OKR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific OKR
     */
    public function show($id): JsonResponse
    {
        try {
            $okr = Okr::with(['user', 'project', 'keyResults'])->find($id);

            if (!$okr) {
                return response()->json([
                    'success' => false,
                    'message' => 'OKR not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'OKR retrieved successfully',
                'data' => $okr
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve OKR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a OKR
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $okr = Okr::find($id);

            if (!$okr) {
                return response()->json([
                    'success' => false,
                    'message' => 'OKR not found'
                ], 404);
            }

            $validated = $request->validate([
                'objective' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|in:company,team,individual',
                'status' => 'sometimes|required|in:active,completed,cancelled',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'nullable|date|after:start_date',
                'project_id' => 'nullable|exists:projects,id'
            ]);

            $okr->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'OKR updated successfully',
                'data' => $okr->load(['user', 'project', 'keyResults'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update OKR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a OKR
     */
    public function destroy($id): JsonResponse
    {
        try {
            $okr = Okr::find($id);

            if (!$okr) {
                return response()->json([
                    'success' => false,
                    'message' => 'OKR not found'
                ], 404);
            }

            $okr->delete();

            return response()->json([
                'success' => true,
                'message' => 'OKR deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete OKR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update key result value
     */
    public function updateKeyResultValue(Request $request, $okrId, $keyResultId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_value' => 'required|numeric|min:0'
            ]);

            $keyResult = KeyResult::where('okr_id', $okrId)
                ->where('id', $keyResultId)
                ->first();

            if (!$keyResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Key result not found'
                ], 404);
            }

            $keyResult->updateCurrentValue($validated['current_value']);

            return response()->json([
                'success' => true,
                'message' => 'Key result value updated successfully',
                'data' => $keyResult->load('okr')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update key result value',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get types
     */
    public function types(): JsonResponse
    {
        try {
            $types = ['company', 'team', 'individual'];

            return response()->json([
                'success' => true,
                'message' => 'Types retrieved successfully',
                'data' => $types
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve types',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
