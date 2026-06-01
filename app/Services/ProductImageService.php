<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ProductImageService
{
    public function __construct(
        private readonly ImageUploadService $imageUploadService
    ) {}

    /**
     * Store main product image with validation
     */
    public function storeMainImage(UploadedFile $file): string
    {
        return $this->imageUploadService->store($file, 'products/main');
    }

    /**
     * Store gallery image with validation
     */
    public function storeGalleryImage(UploadedFile $file): string
    {
        return $this->imageUploadService->store($file, 'products/gallery');
    }

    /**
     * Delete an image
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
