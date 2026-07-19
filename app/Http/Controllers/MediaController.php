<?php

namespace App\Http\Controllers;

use App\Models\MediaDirectory;
use App\Models\User;
use App\Services\StorageConfigService;
use App\Services\DynamicStorageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MediaController extends Controller
{
    public function page()
    {
        if(Auth::user()->can('manage-media')){
            return Inertia::render('media-library');
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }

    }

    /**
     * Scope a Media query to what the current user is allowed to see/touch.
     */
    private function scopeMediaQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        if ($user->type === 'superadmin') {
            $query->where('creator_id', $user->id);
        } elseif ($user->can('manage-any-media')) {
            $query->where('created_by', creatorId());
        } elseif ($user->can('manage-own-media')) {
            $query->where('creator_id', $user->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Scope a MediaDirectory query to what the current user is allowed to see/touch.
     */
    private function scopeDirectoryQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        if ($user->type === 'superadmin') {
            $query->where('created_by', $user->id);
        } elseif ($user->can('manage-any-media-directories')) {
            $query->where('created_by', creatorId());
        } elseif ($user->can('manage-own-media-directories')) {
            $query->where('creator_id', $user->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function index(Request $request)
    {
        if (!Auth::user()->can('manage-media')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $directoryId = $request->input('directory_id');
        $search = trim((string) $request->input('q', ''));
        $perPage = perPage(24);

        $mediaQuery = Media::query();
        if ($directoryId) {
            $mediaQuery->where('directory_id', $directoryId);
        } else {
            // Root view: only files not filed into any folder.
            $mediaQuery->whereNull('directory_id');
        }
        if ($search !== '') {
            $mediaQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . likeEscape($search) . '%')
                  ->orWhere('file_name', 'like', '%' . likeEscape($search) . '%');
            });
        }
        $this->scopeMediaQuery($mediaQuery);

        $media = $mediaQuery->latest()->paginate($perPage)->through(function ($media) {
            $url = getImageUrlPrefix() . '/' . $media->file_name;
            return [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'url' => $url,
                'thumb_url' => $url,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'directory_id' => $media->directory_id,
                'creator_id' => $media->creator_id,
                'created_by' => $media->created_by,
                'created_at' => $media->created_at,
            ];
        });

        $directoriesQuery = MediaDirectory::query();
        if ($directoryId) {
            $directoriesQuery->where('parent_id', $directoryId);
        } else {
            $directoriesQuery->whereNull('parent_id');
        }
        $this->scopeDirectoryQuery($directoriesQuery);
        $directories = $directoriesQuery->get(['id', 'name', 'slug', 'parent_id']);

        $breadcrumbs = [];
        $currentDirectory = null;
        if ($directoryId) {
            $currentDirectory = $this->scopeDirectoryQuery(MediaDirectory::where('id', $directoryId))->firstOrFail();
            $breadcrumbs = $currentDirectory->ancestors();
        }

        return response()->json([
            'media' => $media,
            'directories' => $directories,
            'breadcrumbs' => $breadcrumbs,
            'current_directory' => $currentDirectory ? ['id' => $currentDirectory->id, 'name' => $currentDirectory->name] : null,
        ]);
    }

    private function getUserFriendlyError(\Exception $e, $fileName): string
    {
        $message = $e->getMessage();
        $extension = strtoupper(pathinfo($fileName, PATHINFO_EXTENSION));

        // Handle media library collection errors
        if (str_contains($message, 'was not accepted into the collection')) {
            if (str_contains($message, 'mime:')) {
                return __("File type not allowed : :extension", ['extension' => $extension]);
            }
            return __("File format not supported : :extension", ['extension' => $extension]);
        }

        // Handle storage errors
        if (str_contains($message, 'storage') || str_contains($message, 'disk')) {
            return __("Storage error : :extension", ['extension' => $extension]);
        }

        // Handle file size errors
        if (str_contains($message, 'size') || str_contains($message, 'large')) {
            return __("File too large : :extension", ['extension' => $extension]);
        }

        // Handle permission errors
        if (str_contains($message, 'permission') || str_contains($message, 'denied')) {
            return __("Permission denied : :extension", ['extension' => $extension]);
        }

        // Generic fallback
        return __("Upload failed : :extension", ['extension' => $extension]);
    }

    public function batchStore(Request $request)
    {
        if(Auth::user()->can('create-media')){
             // Check storage limits
            $storageCheck = $this->checkStorageLimit($request->file('files'));
            if ($storageCheck) {
                return $storageCheck;
            }

            $config = StorageConfigService::getStorageConfig();
            $validationRules = StorageConfigService::getFileValidationRules();

            // Custom validation with user-friendly messages
            $validator = \Validator::make($request->all(), [
                'files' => 'required|array',
                'files.*' => array_merge(['file'], $validationRules),
            ], [
                'files.required' => __('Please select at least one file to upload.'),
                'files.array' => __('Files must be provided as an array.'),
                'files.*.file' => __('Each item must be a valid file.'),
                'files.*.mimes' => __('Only specified file types are allowed: :type',[
                        'type' => isset($config['allowed_file_types']) && $config['allowed_file_types']
                            ? strtoupper(str_replace(',', ', ', $config['allowed_file_types']))
                            : __('Please check storage settings')
                    ])
                    ,
                'files.*.max' => __('File size cannot exceed :max KB.', ['max' => $config['max_file_size_kb']]),
            ]);

            // Additional file validation
            foreach ($request->file('files') as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $allowedExtensions = array_map('trim', explode(',', strtolower($config['allowed_file_types'])));

                if (!in_array($extension, $allowedExtensions)) {
                    return response()->json([
                        'message' => __('File type not allowed: :type', ['type' => strtoupper($extension)]),
                        'errors' => [__('Only specified file types are allowed')]
                    ], 422);
                }
            }

            if ($validator->fails()) {
                return response()->json([
                    'message' => __('File validation failed'),
                    'errors' => $validator->errors()->all(),
                    'allowed_types' => $config['allowed_file_types'],
                    'max_size_kb' => $config['max_file_size_kb']
                ], 422);
            }

            $uploadedMedia = [];
            $errors = [];

            foreach ($request->file('files') as $file) {
                try {
                    $media = \App\Services\MediaAttachmentService::upload(
                        $file,
                        'App\Models\User',
                        auth()->id(),
                        'files',
                        auth()->id(),
                        creatorId(),
                        ($request->has('directory_id') && $request->directory_id) ? $request->directory_id : null
                    );

                    $url = Storage::disk($media->disk)->url('media/' . $media->file_name);

                    $uploadedMedia[] = [
                        'id' => $media->id,
                        'name' => $media->name,
                        'file_name' => $media->file_name,
                        'url' => $url,
                        'thumb_url' => $url,
                        'size' => $media->size,
                        'mime_type' => $media->mime_type,
                        'directory_id' => $media->directory_id,
                        'creator_id' => $media->creator_id,
                        'created_by' => $media->created_by,
                        'created_at' => $media->created_at,
                    ];
                } catch (\Exception $e) {
                    // Log the actual error for debugging
                    \Log::error('Media upload failed', [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => $this->getUserFriendlyError($e, $file->getClientOriginalName())
                    ];
                }
            }

            if (count($uploadedMedia) > 0 && empty($errors)) {
                return response()->json([
                    'message' => count($uploadedMedia) . __(' file(s) uploaded successfully'),
                    'data' => $uploadedMedia
                ]);
            } elseif (count($uploadedMedia) > 0 && !empty($errors)) {
                return response()->json([
                    'message' => count($uploadedMedia) . ' uploaded, ' . count($errors) . ' failed',
                    'data' => $uploadedMedia,
                    'errors' => array_column($errors, 'error')
                ]);
            } else {
                return response()->json([
                    'message' => 'Upload failed',
                    'errors' => array_column($errors, 'error')
                ], 422);
            }
        }
        else
        {
            return response()->json(['message' => __('Permission denied')], 403);
        }
    }

    public function download($id)
    {
        if (!Auth::user()->can('download-media')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $media = $this->scopeMediaQuery(Media::where('id', $id))->firstOrFail();

        try {
            // Files are always stored flat under media/{file_name} on $media->disk.
            // Do NOT use $media->getPath()/getUrl() - those resolve through the
            // registered MediaPathGenerator (media/{model_id}/...), which nothing
            // in this app actually writes to.
            $filePath = Storage::disk($media->disk)->path('media/' . $media->file_name);

            if (!file_exists($filePath)) {
                abort(404, __('File not found'));
            }

            // file_name may include a subpath (e.g. "employee_documents/xxx.pdf")
            // for backfilled records - the download filename must be a bare basename.
            return response()->download($filePath, basename($media->file_name));
        } catch (\Exception $e) {
            abort(404, __('File storage unavailable'));
        }
    }

    public function renameMedia(Request $request, $id)
    {
        if (!Auth::user()->can('manage-media')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => __('File name is required.'),
        ]);

        $media = $this->scopeMediaQuery(Media::where('id', $id))->firstOrFail();
        $media->update(['name' => $request->name]);

        return response()->json([
            'message' => __('The file has been renamed.'),
            'media' => ['id' => $media->id, 'name' => $media->name],
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        if (!Auth::user()->can('delete-media')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $items = $this->scopeMediaQuery(Media::whereIn('id', $request->ids))->get();

        foreach ($items as $media) {
            \App\Services\MediaAttachmentService::deleteMedia($media);
        }

        return response()->json([
            'message' => count($items) . __(' file(s) deleted'),
            'deleted' => $items->pluck('id'),
        ]);
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('delete-media')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $media = $this->scopeMediaQuery(Media::where('id', $id))->firstOrFail();

        \App\Services\MediaAttachmentService::deleteMedia($media);

        return response()->json(['message' => __('The media has been deleted.')]);
    }

    private function checkStorageLimit($files)
    {
        $user = auth()->user();
        if ($user->type === 'superadmin') return null;

        $creator = ($user->type === 'company') ? $user : User::find($user->created_by);
        if (!$creator) {
            return response()->json([
                'message' => __('Creator not found'),
                'errors' => [__('Please contact administrator')]
            ], 422);
        }

        if ($creator->storage_limit == -1) return null;

        $limit = $creator->storage_limit * 1024; // Convert KB to Bytes
        $uploadSize = collect($files)->sum('size');
        $currentUsage = Media::where('created_by', $creator->id)->sum('size');

        if (($currentUsage + $uploadSize) > $limit) {
            return response()->json([
                'message' => __('Storage limit exceeded'),
                'errors' => [__('Please delete files or upgrade plan')]
            ], 422);
        }

        return null;
    }

    public function createDirectory(Request $request)
    {
        if (!Auth::user()->can('create-media-directories')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:media_directories,id',
        ], [
            'name.required' => __('Directory name is required.'),
            'name.string' => __('Directory name must be a valid string.'),
            'name.max' => __('Directory name must not exceed 255 characters.'),
        ]);

        if ($request->filled('parent_id')) {
            $this->scopeDirectoryQuery(MediaDirectory::where('id', $request->parent_id))->firstOrFail();
        }

        $slug = \Str::slug($request->name . '-' . time());

        $directory = MediaDirectory::create([
            'name' => $request->name,
            'slug' => $slug,
            'parent_id' => $request->parent_id,
            'created_by' => creatorId(),
            'creator_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => __('The directory has been created successfully.'),
            'directory' => $directory
        ]);
    }

    public function updateMediaDirectory(Request $request, $mediaId)
    {
        if (!Auth::user()->can('manage-media')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $request->validate([
            'directory_id' => 'nullable|exists:media_directories,id',
        ], [
            'directory_id.exists' => __('Selected directory does not exist.'),
        ]);

        $media = $this->scopeMediaQuery(Media::where('id', $mediaId))->firstOrFail();

        if ($request->filled('directory_id')) {
            $this->scopeDirectoryQuery(MediaDirectory::where('id', $request->directory_id))->firstOrFail();
        }

        $media->update(['directory_id' => $request->directory_id]);

        return response()->json([
            'message' => __('The media moved successfully.')
        ]);
    }

    public function updateDirectory(Request $request, $id)
    {
        if (!Auth::user()->can('edit-media-directories')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => __('Directory name is required.'),
            'name.string' => __('Directory name must be a valid string.'),
            'name.max' => __('Directory name must not exceed 255 characters.'),
        ]);

        $directory = $this->scopeDirectoryQuery(MediaDirectory::where('id', $id))->firstOrFail();
        $slug = \Str::slug($request->name . '-' . time());

        $directory->update([
            'name' => $request->name,
            'slug' => $slug,
        ]);

        return response()->json([
            'message' => __('The directory details are updated successfully.'),
            'directory' => $directory
        ]);
    }

    public function destroyDirectory($id)
    {
        if (!Auth::user()->can('delete-media-directories')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $directory = $this->scopeDirectoryQuery(MediaDirectory::where('id', $id))->firstOrFail();
        $directory->delete();

        return response()->json([
            'message' => __('The directory has been deleted.')
        ]);
    }
}
