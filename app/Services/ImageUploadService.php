<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageUploadService
{
    /**
     * Allowed MIME types for images
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    /**
     * Allowed file extensions
     */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    /**
     * Maximum file size in kilobytes (5MB)
     */
    private const MAX_FILE_SIZE = 5120;

    /**
     * Store an uploaded image with security checks
     *
     * @param UploadedFile $file
     * @param string $directory Base directory (e.g., 'products', 'categories')
     * @param string|null $disk Storage disk name
     * @return string The stored file path
     * @throws ValidationException
     */
    public function store(UploadedFile $file, string $directory, ?string $disk = 'public'): string
    {
        $this->validateFile($file);

        // Ensure disk defaults to 'public' if null is passed
        $disk = $disk ?? 'public';

        // Generate unique filename with UUID + timestamp
        $extension = $file->getClientOriginalExtension();
        $filename = $this->generateFilename($extension);

        // Create subdirectory structure based on date for better organization
        $subdir = date('Y/m');
        $path = "{$directory}/{$subdir}";

        // Store the file
        $storedPath = $file->storeAs($path, $filename, $disk);

        return $storedPath;
    }

    /**
     * Store multiple images
     *
     * @param array $files Array of UploadedFile
     * @param string $directory
     * @param string|null $disk
     * @return array Array of stored paths
     */
    public function storeMultiple(array $files, string $directory, ?string $disk = 'public'): array
    {
        return array_map(
            fn (UploadedFile $file) => $this->store($file, $directory, $disk),
            $files
        );
    }

    /**
     * Delete an image from storage
     *
     * @param string|null $path
     * @param string|null $disk
     * @return bool
     */
    public function delete(?string $path, ?string $disk = 'public'): bool
    {
        if (empty($path)) {
            return false;
        }

        $disk = $disk ?? 'public';

        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    /**
     * Delete multiple images
     *
     * @param array $paths
     * @param string|null $disk
     * @return int Number of deleted files
     */
    public function deleteMultiple(array $paths, ?string $disk = 'public'): int
    {
        $deleted = 0;
        foreach ($paths as $path) {
            if ($this->delete($path, $disk)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    /**
     * Replace an existing image with a new one
     *
     * @param UploadedFile $newFile
     * @param string|null $oldPath
     * @param string $directory
     * @param string|null $disk
     * @return string New file path
     * @throws ValidationException
     */
    public function replace(UploadedFile $newFile, ?string $oldPath, string $directory, ?string $disk = 'public'): string
    {
        $newPath = $this->store($newFile, $directory, $disk);

        // Delete old file after successful storage
        if ($oldPath && $oldPath !== $newPath) {
            $this->delete($oldPath, $disk);
        }

        return $newPath;
    }

    /**
     * Get the full URL for a stored image
     *
     * @param string|null $path
     * @param string|null $disk
     * @return string|null
     */
    public function getUrl(?string $path, ?string $disk = 'public'): ?string
    {
        if (empty($path)) {
            return null;
        }

        // If already a full URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = $disk ?? 'public';

        return Storage::disk($disk)->url($path);
    }

    /**
     * Validate file before upload
     *
     * @param UploadedFile $file
     * @throws ValidationException
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE * 1024) {
            throw ValidationException::withMessages([
                'image' => ["حجم فایل نباید بیشتر از " . (self::MAX_FILE_SIZE / 1024) . " مگابایت باشد."]
            ]);
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'image' => ['نوع فایل مجاز نیست. فقط تصاویر (jpg, png, gif, webp, svg) مجاز هستند.']
            ]);
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'image' => ['پسوند فایل مجاز نیست.']
            ]);
        }

        // Additional security: verify image dimensions
        $dimensions = getimagesize($file->getRealPath());
        if ($dimensions === false && $mimeType !== 'image/svg+xml') {
            throw ValidationException::withMessages([
                'image' => ['فایل تصویر معتبر نیست.']
            ]);
        }
    }

    /**
     * Generate a unique filename
     *
     * @param string $extension
     * @return string
     */
    private function generateFilename(string $extension): string
    {
        $uuid = Str::uuid()->toString();
        $timestamp = now()->format('His');
        $random = Str::random(8);

        return "{$timestamp}_{$uuid}_{$random}.{$extension}";
    }

    /**
     * Clean up unused images in a directory (orphaned files)
     *
     * @param string $directory
     * @param array $usedPaths Array of paths that are currently in use
     * @param string|null $disk
     * @return int Number of deleted files
     */
    public function cleanup(string $directory, array $usedPaths, ?string $disk = 'public'): int
    {
        $disk = $disk ?? 'public';
        $deleted = 0;

        // Get all files in the directory
        $allFiles = Storage::disk($disk)->allFiles($directory);

        // Filter out used paths
        $unusedFiles = array_diff($allFiles, $usedPaths);

        foreach ($unusedFiles as $file) {
            if (Storage::disk($disk)->delete($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
