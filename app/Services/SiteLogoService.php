<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SiteLogoService
{
    public function store(UploadedFile $file, string $key): string
    {
        return $file->store("site/{$key}", 'public');
    }

    public function delete(?string $storagePath): void
    {
        if ($storagePath && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }
}
