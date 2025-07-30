<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\Folder;

class FileController extends Controller
{
    public function index()
    {
        try {
            $files = File::with('folder')->orderBy('uploaded_at', 'desc')->get();

            // ✅ Debug logging
            Log::info('Files fetched from database:', $files->toArray());

            return response()->json([
                'success' => true,
                'data' => $files,
                'count' => $files->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching files: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('File upload request received:', $request->all());
            Log::info('Files in request:', ['has_files' => $request->hasFile('files') ? 'Yes' : 'No']);


            $validator = Validator::make($request->all(), [
                'files' => 'required|array',
                'files.*' => 'file|max:10240',
                'folder' => 'nullable|string|exists:folders,name',
                'folder_id' => 'nullable|integer|exists:folders,id',
            ]);

            if ($validator->fails()) {
                Log::error('Validation failed:', $validator->errors()->toArray());
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $uploadedFiles = [];
            $user = $request->user();
            $folderId = null;

            // ✅ Prioritas: gunakan folder_id jika ada, baru folder name
            if ($request->has('folder_id') && $request->folder_id) {
                $folderId = (int) $request->folder_id;
                Log::info('Using folder_id:', [$folderId]);
            } elseif ($request->has('folder')) {
                $folder = Folder::where('name', $request->folder)->first();
                if ($folder) {
                    $folderId = $folder->id;
                    Log::info('Found folder by name:', ['name' => $request->folder, 'id' => $folderId]);
                }
            }

            Log::info('Final folder_id to use:', [$folderId]);

            foreach ($request->file('files') as $index => $file) {
                Log::info("Processing file {$index}:", [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getClientMimeType()
                ]);

                $filename = $file->getClientOriginalName();
                $path = $file->storeAs('documents', $filename, 'public');

                Log::info("File stored at:", [$path]);

                $fileRecord = File::create([
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
                    'folder_id' => $folderId, // ✅ Ini akan null jika tidak ada folder
                ]);

                Log::info("File record created:", $fileRecord->toArray());

                // ✅ Activity Log
                ActivityLog::create([
                    'user_id' => $user?->id,
                    'user_name' => $user ? $user->name : 'System',
                    'user_avatar' => $user?->avatar ?? null,
                    'user_color' => $user?->color ?? null,
                    'action' => 'uploaded file',
                    'target' => $file->getClientOriginalName(),
                    'project' => null,
                    'type' => 'file',
                    'details' => "File \"{$file->getClientOriginalName()}\" was uploaded",
                    'metadata' => [
                        'fileSize' => $this->formatFileSize($file->getSize()),
                        'fileType' => $file->getClientMimeType(),
                        'originalName' => $file->getClientOriginalName(),
                        'storedPath' => $path,
                        'folderId' => $folderId,
                        'folderName' => $folderId ? Folder::find($folderId)?->name : 'Root'
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                $uploadedFiles[] = $fileRecord;
            }

            Log::info('All files processed successfully:', [count($uploadedFiles)]);

            return response()->json([
                'success' => true,
                'data' => $uploadedFiles,
                'message' => count($uploadedFiles) . ' files uploaded successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {

        $file = File::with('folder')->find($id);
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $file]);
    }

    public function update(Request $request, $id)
    {
        if (!$request->user()->hasPermission('edit_documents')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit documents'
            ], 403);
        }
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

        $user = $request->user();
        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user ? $user->name : 'System',
            'user_avatar' => $user?->avatar ?? null,
            'user_color' => $user?->color ?? null,
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

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->hasPermission('delete_documents')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete documents'
            ], 403);
        }
        try {
            $file = File::find($id);
            if (!$file) {
                return response()->json(['success' => false, 'message' => 'File not found'], 404);
            }

            $fileName = $file->name;
            $filePath = $file->path;

            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            $file->delete();

            $user = request()->user();
            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user ? $user->name : 'System',
                'user_avatar' => $user?->avatar ?? null,
                'user_color' => $user?->color ?? null,
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
        } catch (\Exception $e) {
            Log::error('File deletion error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Delete failed'], 500);
        }
    }

    public function download(Request $request, $id)
    {
        if (!$request->user()->hasPermission('download_documents')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to download documents'
            ], 403);
        }
        $file = File::find($id);
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        if (!Storage::disk('public')->exists($file->path)) {
            return response()->json(['success' => false, 'message' => 'File not found on disk'], 404);
        }

        $user = request()->user();
        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user ? $user->name : 'System',
            'user_avatar' => $user?->avatar ?? null,
            'user_color' => $user?->color ?? null,
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

    public function preview(Request $request, $id)
    {
        if (!$request->user()->hasPermission('preview_documents')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to preview documents'
            ], 403);
        }
        $file = File::find($id);
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        if (!Storage::disk('public')->exists($file->path)) {
            return response()->json(['success' => false, 'message' => 'File not found on disk'], 404);
        }

        $user = request()->user();
        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user ? $user->name : 'System',
            'user_avatar' => $user?->avatar ?? null,
            'user_color' => $user?->color ?? null,
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
