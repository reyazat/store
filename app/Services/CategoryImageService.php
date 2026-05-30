<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CategoryImageService
{
    public function store(UploadedFile $file): string
    {
        return $file->store('categories', 'public');
    }

    public function delete(?string $storagePath): void
    {
        if (!$storagePath) {
            return;
        }

        if (str_starts_with($storagePath, 'http://') || str_starts_with($storagePath, 'https://')) {
            return;
        }

        if (Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }
}
