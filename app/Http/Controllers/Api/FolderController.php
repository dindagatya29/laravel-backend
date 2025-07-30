<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FolderController extends Controller
{
    // ✅ 1. Get all folders
    public function index(Request $request)
{
    $user = $request->user();

    // if (!$user || !$user->hasPermission('view_folders')) {
    //     return response()->json(['message' => 'Unauthorized'], 403);
    // }

    $folders = Folder::orderBy('created_at', 'asc')->get();
    return response()->json(['success' => true, 'data' => $folders]);
}


    // ✅ 2. Create new folder
    public function store(Request $request)
    {
        // if (!$request->user()->hasPermission('create_folders')) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'You do not have permission to create folders'
        //     ], 403);
        // }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:folders,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $folder = Folder::create([
            'name' => $request->name
        ]);

        return response()->json(['success' => true, 'data' => $folder], 201);
    }

    // ✅ 3. Rename (update) folder
    public function update(Request $request, $id)
    {
        // if (!$request->user()->hasPermission('rename_folders')) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'You do not have permission to rename folders'
        //     ], 403);
        // }

        $folder = Folder::find($id);

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:folders,name,' . $folder->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $folder->name = $request->name;
        $folder->save();

        return response()->json(['success' => true, 'data' => $folder]);
    }

    // ✅ 4. Delete folder
    public function destroy(Request $request, $id)
    {
        // if (!$request->user()->hasPermission('delete_folders')) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'You do not have permission to delete folders'
        //     ], 403);
        // }

        $folder = Folder::find($id);

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $folder->delete();

        return response()->json(['success' => true, 'message' => 'Folder deleted successfully']);
    }
}
