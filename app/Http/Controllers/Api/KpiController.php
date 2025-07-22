<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kpi;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class KpiController extends Controller
{
    /**
     * Get all KPIs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Kpi::with(['user', 'project'])->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by category
            if ($request->has('category') && $request->category !== 'all') {
                $query->where('category', $request->category);
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
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 20);
            $kpis = $query->paginate($perPage);

            // Transform data
            $transformedKpis = $kpis->getCollection()->map(function ($kpi) {
                return [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'description' => $kpi->description,
                    'category' => $kpi->category,
                    'unit' => $kpi->unit,
                    'target_value' => $kpi->target_value,
                    'current_value' => $kpi->current_value,
                    'baseline_value' => $kpi->baseline_value,
                    'progress' => $kpi->progress,
                    'frequency' => $kpi->frequency,
                    'direction' => $kpi->direction,
                    'status' => $kpi->status,
                    'start_date' => $kpi->start_date->format('Y-m-d'),
                    'end_date' => $kpi->end_date?->format('Y-m-d'),
                    'status_color' => $kpi->status_color,
                    'direction_icon' => $kpi->direction_icon,
                    'frequency_label' => $kpi->frequency_label,
                    'is_on_track' => $kpi->isOnTrack(),
                    'user' => $kpi->user ? [
                        'id' => $kpi->user->id,
                        'name' => $kpi->user->name,
                        'avatar' => $kpi->user->avatar
                    ] : null,
                    'project' => $kpi->project ? [
                        'id' => $kpi->project->id,
                        'name' => $kpi->project->name
                    ] : null,
                    'created_at' => $kpi->created_at ? $kpi->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $kpi->updated_at ? $kpi->updated_at->format('Y-m-d H:i:s') : null,

                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'KPIs retrieved successfully',
                'data' => $transformedKpis,
                'pagination' => [
                    'current_page' => $kpis->currentPage(),
                    'last_page' => $kpis->lastPage(),
                    'per_page' => $kpis->perPage(),
                    'total' => $kpis->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve KPIs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get KPI statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => Kpi::count(),
                'active' => Kpi::active()->count(),
                'completed' => Kpi::where('status', 'completed')->count(),
                'on_track' => Kpi::active()->get()->filter(fn($kpi) => $kpi->isOnTrack())->count(),
                'at_risk' => Kpi::active()->get()->filter(fn($kpi) => !$kpi->isOnTrack())->count(),
                'by_category' => Kpi::select('category')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('category')
                    ->pluck('count', 'category'),
                'by_status' => Kpi::select('status')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
            ];

            return response()->json([
                'success' => true,
                'message' => 'KPI statistics retrieved successfully',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve KPI statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new KPI
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'required|string|max:255',
                'unit' => 'required|string|max:255',
                'target_value' => 'required|numeric|min:0',
                'current_value' => 'nullable|numeric|min:0',
                'baseline_value' => 'nullable|numeric|min:0',
                'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
                'direction' => 'required|in:increase,decrease,maintain',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
                'project_id' => 'nullable|exists:projects,id'
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

            $kpi = Kpi::create([
                'user_id' => $user->id,
                'project_id' => $validated['project_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'unit' => $validated['unit'],
                'target_value' => $validated['target_value'],
                'current_value' => $validated['current_value'] ?? 0,
                'baseline_value' => $validated['baseline_value'] ?? 0,
                'frequency' => $validated['frequency'],
                'direction' => $validated['direction'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KPI created successfully',
                'data' => $kpi->load(['user', 'project'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create KPI',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific KPI
     */
    public function show($id): JsonResponse
    {
        try {
            $kpi = Kpi::with(['user', 'project'])->find($id);

            if (!$kpi) {
                return response()->json([
                    'success' => false,
                    'message' => 'KPI not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'KPI retrieved successfully',
                'data' => $kpi
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve KPI',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a KPI
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $kpi = Kpi::find($id);

            if (!$kpi) {
                return response()->json([
                    'success' => false,
                    'message' => 'KPI not found'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'sometimes|required|string|max:255',
                'unit' => 'sometimes|required|string|max:255',
                'target_value' => 'sometimes|required|numeric|min:0',
                'current_value' => 'nullable|numeric|min:0',
                'baseline_value' => 'nullable|numeric|min:0',
                'frequency' => 'sometimes|required|in:daily,weekly,monthly,quarterly,yearly',
                'direction' => 'sometimes|required|in:increase,decrease,maintain',
                'status' => 'sometimes|required|in:active,paused,completed,cancelled',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'nullable|date|after:start_date',
                'project_id' => 'nullable|exists:projects,id'
            ]);

            $kpi->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'KPI updated successfully',
                'data' => $kpi->load(['user', 'project'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update KPI',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a KPI
     */
    public function destroy($id): JsonResponse
    {
        try {
            $kpi = Kpi::find($id);

            if (!$kpi) {
                return response()->json([
                    'success' => false,
                    'message' => 'KPI not found'
                ], 404);
            }

            $kpi->delete();

            return response()->json([
                'success' => true,
                'message' => 'KPI deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete KPI',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update KPI current value
     */
    public function updateValue(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_value' => 'required|numeric|min:0'
            ]);

            $kpi = Kpi::find($id);

            if (!$kpi) {
                return response()->json([
                    'success' => false,
                    'message' => 'KPI not found'
                ], 404);
            }

            $kpi->updateCurrentValue($validated['current_value']);

            return response()->json([
                'success' => true,
                'message' => 'KPI value updated successfully',
                'data' => $kpi->load(['user', 'project'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update KPI value',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = Kpi::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->pluck('category');

            return response()->json([
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
