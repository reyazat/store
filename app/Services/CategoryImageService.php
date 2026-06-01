<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class CategoryImageService
{
    public function __construct(
        private readonly ImageUploadService $imageUploadService
    ) {}

    /**
     * Store category image with validation
     */
    public function store(UploadedFile $file): string
    {
        return $this->imageUploadService->store($file, 'categories');
    }

    /**
     * Delete a category image
     */
    public function delete(?string $storagePath): void
    {
        $this->imageUploadService->delete($storagePath);
    }

    /**
     * Get full URL for image
     */
    public function getUrl(?string $path): ?string
    {
        return $this->imageUploadService->getUrl($path);
    }
}
