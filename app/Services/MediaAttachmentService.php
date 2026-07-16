<?php

namespace App\Services;

use App\Models\MediaDirectory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Central place every package routes file storage through, so all business
 * files end up as real rows in the shared `media` table (visible/manageable
 * in the Media Library), regardless of which package's feature uploaded them.
 *
 * Files are always stored flat under media/{file_name} on the active disk,
 * never through Spatie's own addMedia()/conversion pipeline, since the
 * registered MediaPathGenerator (media/{model_id}/...) is not what anything
 * here actually writes to, and 35+ frontend consumers reconstruct URLs
 * assuming this flat layout.
 */
class MediaAttachmentService
{
    /**
     * Upload a brand-new file and create a real Media row for it.
     */
    public static function upload(
        UploadedFile $file,
        string $modelType,
        int $modelId,
        string $collectionName,
        ?int $creatorId,
        ?int $createdBy,
        ?int $directoryId = null
    ): Media {
        DynamicStorageService::configureDynamicDisks();
        $activeDisk = StorageConfigService::getActiveDisk();

        $fileName = $file->getClientOriginalName();
        $hashedName = $file->hashName();
        $storedPath = $file->storeAs('media', $hashedName, $activeDisk);

        try {
            $media = new Media();
            $media->model_type = $modelType;
            $media->model_id = $modelId;
            $media->collection_name = $collectionName;
            $media->name = pathinfo($fileName, PATHINFO_FILENAME);
            $media->file_name = $hashedName;
            $media->mime_type = $file->getMimeType();
            $media->disk = $activeDisk;
            $media->size = $file->getSize();
            $media->manipulations = [];
            $media->custom_properties = [];
            $media->generated_conversions = [];
            $media->responsive_images = [];
            $media->uuid = \Str::uuid();
            $media->creator_id = $creatorId;
            $media->created_by = $createdBy;
            $media->directory_id = $directoryId;
            $media->save();

            return $media;
        } catch (\Throwable $e) {
            if ($storedPath && Storage::disk($activeDisk)->exists($storedPath)) {
                Storage::disk($activeDisk)->delete($storedPath);
            }
            throw $e;
        }
    }

    /**
     * Idempotent: resolve an existing Media row for a file that's already on
     * disk (or already has a Media row from a prior media.batch upload), or
     * create one pointing at the existing physical file (no data movement).
     *
     * $relativePath is relative to the media/ root, e.g. a bare MediaPicker
     * file_name ("abc123.pdf") or an upload_file()-style subpath
     * ("employee_documents/abc123.pdf").
     */
    public static function resolveOrBackfill(
        string $relativePath,
        string $modelType,
        int $modelId,
        string $collectionName,
        ?int $creatorId,
        ?int $createdBy,
        ?int $directoryId = null
    ): ?Media {
        $relativePath = ltrim($relativePath, '/');

        $existing = Media::where('file_name', $relativePath)->first();
        if ($existing) {
            // Organize a currently-unfiled file into the suggested folder, but
            // never override a location the user (or an earlier link) already chose.
            if ($existing->directory_id === null && $directoryId !== null) {
                $existing->update(['directory_id' => $directoryId]);
            }
            return $existing;
        }

        DynamicStorageService::configureDynamicDisks();
        $activeDisk = StorageConfigService::getActiveDisk();
        $candidateDisks = array_unique([$activeDisk, 'public', 's3', 'wasabi']);

        foreach ($candidateDisks as $disk) {
            try {
                if (!Storage::disk($disk)->exists('media/' . $relativePath)) {
                    continue;
                }

                $media = new Media();
                $media->model_type = $modelType;
                $media->model_id = $modelId;
                $media->collection_name = $collectionName;
                $media->name = pathinfo($relativePath, PATHINFO_FILENAME);
                $media->file_name = $relativePath;
                $media->mime_type = Storage::disk($disk)->mimeType('media/' . $relativePath) ?: 'application/octet-stream';
                $media->disk = $disk;
                $media->size = Storage::disk($disk)->size('media/' . $relativePath);
                $media->manipulations = [];
                $media->custom_properties = ['backfilled' => true];
                $media->generated_conversions = [];
                $media->responsive_images = [];
                $media->uuid = \Str::uuid();
                $media->creator_id = $creatorId;
                $media->created_by = $createdBy;
                $media->directory_id = $directoryId;
                $media->save();

                return $media;
            } catch (\Exception $e) {
                continue;
            }
        }

        \Log::warning('MediaAttachmentService::resolveOrBackfill could not locate file on any disk', [
            'path' => $relativePath,
            'model_type' => $modelType,
            'model_id' => $modelId,
        ]);

        return null;
    }

    /**
     * Find-or-create a top-level MediaDirectory, so re-running backfills
     * never creates duplicate folders.
     */
    public static function ensureDirectory(string $name, int $createdBy, ?int $creatorId = null, ?int $parentId = null): int
    {
        $directory = MediaDirectory::where('name', $name)
            ->where('created_by', $createdBy)
            ->where('parent_id', $parentId)
            ->first();

        if ($directory) {
            return $directory->id;
        }

        $directory = MediaDirectory::create([
            'name' => $name,
            'slug' => \Str::slug($name . '-' . time() . '-' . $createdBy),
            'parent_id' => $parentId,
            'created_by' => $createdBy,
            'creator_id' => $creatorId ?? $createdBy,
        ]);

        return $directory->id;
    }

    /**
     * Disk-aware delete: removes the physical file and the Media row.
     */
    public static function deleteMedia(Media $media): bool
    {
        try {
            Storage::disk($media->disk)->delete('media/' . $media->file_name);
        } catch (\Exception $e) {
            // Storage disk unavailable; still remove the DB row.
        }

        return (bool) $media->delete();
    }
}
