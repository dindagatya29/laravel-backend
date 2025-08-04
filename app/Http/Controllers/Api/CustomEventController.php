<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomEvent;
use Illuminate\Support\Facades\Validator;

class CustomEventController extends Controller
{
    public function index()
    {
        $events = CustomEvent::orderBy('date')->get();
        return response()->json(['success' => true, 'data' => $events]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'color' => 'nullable|string|max:32',
            'type' => 'nullable|string|max:32',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        $event = CustomEvent::create([
            'title' => $request->title,
            'description' => $request->description,
            'date' => $request->date,
            'color' => $request->color,
            'type' => $request->type ?? 'custom',
            'created_by' => $request->user() ? $request->user()->id : null,
        ]);
        return response()->json(['success' => true, 'data' => $event], 201);
    }
} 