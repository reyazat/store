<?php

namespace App\Services;

use App\Models\PromoBanner;
use App\Repositories\Contracts\PromoBannerRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PromoBannerService
{
    public function __construct(
        private readonly PromoBannerRepositoryInterface $bannerRepository
    ) {}

    public function storeImage(UploadedFile $file): string
    {
        return $file->store('promo-banners', 'public');
    }

    public function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function create(array $data, ?UploadedFile $image = null): PromoBanner
    {
        if ($image) {
            $data['image'] = $this->storeImage($image);
        }

        if (!isset($data['sort_order'])) {
            $placement = $data['placement'] ?? 'home';
            $data['sort_order'] = $this->bannerRepository->getAll($placement)->count();
        }

        return $this->bannerRepository->create($data);
    }

    public function update(PromoBanner $banner, array $data, ?UploadedFile $image = null): PromoBanner
    {
        if ($image) {
            $this->deleteImage($banner->image);
            $data['image'] = $this->storeImage($image);
        }

        return $this->bannerRepository->update($banner, $data);
    }

    public function delete(PromoBanner $banner): void
    {
        $this->deleteImage($banner->image);
        $this->bannerRepository->delete($banner);
    }
}
