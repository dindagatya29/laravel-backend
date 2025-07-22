<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TimeEntryController extends Controller
{
    public function index(Request $request)
    {
        $entries = TimeEntry::with(['task.project'])->orderBy('start_time', 'desc')->get();
        // Format response agar frontend dapat taskName dan projectName
        $data = $entries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'taskName' => $entry->task ? $entry->task->title : null,
                'projectName' => $entry->task && $entry->task->project ? $entry->task->project->name : null,
                'startTime' => $entry->start_time,
                'endTime' => $entry->end_time,
                'duration' => $entry->duration,
                'description' => $entry->description,
            ];
        });
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'duration' => 'nullable|integer|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $entry = TimeEntry::create([
            'task_id' => $request->task_id,
            'user_id' => 1, // Default user ID, atau hapus jika tidak butuh
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration' => $request->duration,
        ]);
        return response()->json(['success' => true, 'data' => $entry], 201);
    }

    public function show($id)
    {
        $entry = TimeEntry::with(['task'])->find($id);
        if (!$entry) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $entry]);
    }

    public function update(Request $request, $id)
    {
        $entry = TimeEntry::find($id);
        if (!$entry) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'duration' => 'nullable|integer|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $entry->update($validator->validated());
        return response()->json(['success' => true, 'data' => $entry]);
    }

    public function destroy($id)
    {
        $entry = TimeEntry::find($id);
        if (!$entry) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $entry->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }
} 