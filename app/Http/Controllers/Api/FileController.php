<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    public function index()
    {
        $files = File::orderBy('uploaded_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $files]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*' => 'file|max:10240', // max 10MB per file
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        
        $uploadedFiles = [];
        $user = $request->user(); // Get authenticated user from request
        
        foreach ($request->file('files') as $file) {
            $filename = time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('documents', $filename, 'public');
            $meta = File::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $user ? $user->name : 'System',
                'uploaded_at' => now(),
                'tags' => [],
                'project_id' => null,
                'task_id' => null,
                'version' => 1,
                'is_latest' => true,
            ]);
            
            // Log activity for file upload
            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user ? $user->name : 'System',
                'user_avatar' => $user?->avatar,
                'user_color' => $user?->color,
                'action' => 'uploaded file',
                'target' => $file->getClientOriginalName(),
                'project' => null,
                'type' => 'file',
                'details' => "File \"{$file->getClientOriginalName()}\" was uploaded",
                'metadata' => [
                    'fileSize' => $this->formatFileSize($file->getSize()),
                    'fileType' => $file->getClientMimeType(),
                    'originalName' => $file->getClientOriginalName(),
                    'storedPath' => $path
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            $uploadedFiles[] = $meta;
        }
        
        return response()->json(['success' => true, 'data' => $uploadedFiles], 201);
    }

    public function show($id)
    {
        $file = File::find($id);
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $file]);
    }

    public function update(Request $request, $id)
    {
        $file = File::find($id);
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'tags' => 'sometimes|array',
            'project_id' => 'sometimes|nullable|exists:projects,id',
            'task_id' => 'sometimes|nullable|exists:tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $oldData = $file->toArray();
        $file->update($request->only(['name', 'tags', 'project_id', 'task_id']));
        
        // Log activity for file update
        $user = $request->user();
        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user ? $user->name : 'System',
            'user_avatar' => $user?->avatar,
            'user_color' => $user?->color,
            'action' => 'updated file',
            'target' => $file->name,
            'project' => null,
            'type' => 'file',
            'details' => "File \"{$file->name}\" was updated",
            'metadata' => [
                'oldData' => $oldData,
                'newData' => $file->toArray(),
                'changes' => $request->only(['name', 'tags', 'project_id', 'task_id'])
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json(['success' => true, 'data' => $file]);
    }

    public function destroy($id)
    {
        $file = File::find($id);
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        $fileName = $file->name;
        $filePath = $file->path;
        
        // Delete file from storage
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
        
        // Delete file record
        $file->delete();
        
        // Log activity for file deletion
        $user = request()->user();
        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user ? $user->name : 'System',
            'user_avatar' => $user?->avatar,
            'user_color' => $user?->color,
            'action' => 'deleted file',
            'target' => $fileName,
            'project' => null,
            'type' => 'file',
            'details' => "File \"{$fileName}\" was deleted",
            'metadata' => [
                'fileSize' => $this->formatFileSize($file->size),
                'fileType' => $file->type,
                'originalPath' => $filePath
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return response()->json(['success' => true, 'message' => 'File deleted successfully']);
    }

    public function download($id)
    {
        $file = File::find($id);
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        if (!Storage::disk('public')->exists($file->path)) {
            return response()->json(['success' => false, 'message' => 'File not found on disk'], 404);
        }

        // Log activity for file download
        $user = request()->user();
        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user ? $user->name : 'System',
            'user_avatar' => $user?->avatar,
            'user_color' => $user?->color,
            'action' => 'downloaded file',
            'target' => $file->name,
            'project' => null,
            'type' => 'file',
            'details' => "File \"{$file->name}\" was downloaded",
            'metadata' => [
                'fileSize' => $this->formatFileSize($file->size),
                'fileType' => $file->type
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return response()->download(Storage::disk('public')->path($file->path), $file->name);
    }

    public function preview($id)
    {
        $file = File::find($id);
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        if (!Storage::disk('public')->exists($file->path)) {
            return response()->json(['success' => false, 'message' => 'File not found on disk'], 404);
        }

        // Log activity for file preview
        $user = request()->user();
        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user ? $user->name : 'System',
            'user_avatar' => $user?->avatar,
            'user_color' => $user?->color,
            'action' => 'previewed file',
            'target' => $file->name,
            'project' => null,
            'type' => 'file',
            'details' => "File \"{$file->name}\" was previewed",
            'metadata' => [
                'fileSize' => $this->formatFileSize($file->size),
                'fileType' => $file->type
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return response()->file(Storage::disk('public')->path($file->path));
    }

    private function formatFileSize($bytes)
    {
        if ($bytes === 0) return "0 Bytes";
        $k = 1024;
        $sizes = ["Bytes", "KB", "MB", "GB"];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . " " . $sizes[$i];
    }
} 