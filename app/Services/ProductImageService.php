<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    public function storeMainImage(UploadedFile $file): string
    {
        return $file->store('products/main', 'public');
    }

    public function storeGalleryImage(UploadedFile $file): string
    {
        return $file->store('products/gallery', 'public');
    }

    public function delete(?string $storagePath): void
    {
        if ($storagePath && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }
}
