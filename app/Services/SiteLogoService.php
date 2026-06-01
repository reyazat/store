<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class SiteLogoService
{
    public function __construct(
        private readonly ImageUploadService $imageUploadService
    ) {}

    /**
     * Store site logo/favicon with validation
     */
    public function store(UploadedFile $file, string $key): string
    {
        return $this->imageUploadService->store($file, "site/{$key}");
    }

    /**
     * Delete site logo/favicon
     */
    public function delete(?string $storagePath): void
    {
        $this->imageUploadService->delete($storagePath);
    }

    /**
     * Get full URL for logo/favicon
     */
    public function getUrl(?string $path): ?string
    {
        return $this->imageUploadService->getUrl($path);
    }
}
