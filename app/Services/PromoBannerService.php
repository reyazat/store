<?php

namespace App\Services;

use App\Models\PromoBanner;
use App\Repositories\Contracts\PromoBannerRepositoryInterface;
use Illuminate\Http\UploadedFile;

class PromoBannerService
{
    public function __construct(
        private readonly PromoBannerRepositoryInterface $bannerRepository,
        private readonly ImageUploadService $imageUploadService,
    ) {}

    public function create(array $data, ?UploadedFile $image = null): PromoBanner
    {
        if ($image) {
            $data['image'] = $this->imageUploadService->store($image, 'promo-banners');
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
            $this->imageUploadService->delete($banner->image);
            $data['image'] = $this->imageUploadService->store($image, 'promo-banners');
        }

        return $this->bannerRepository->update($banner, $data);
    }

    public function delete(PromoBanner $banner): void
    {
        $this->imageUploadService->delete($banner->image);
        $this->bannerRepository->delete($banner);
    }
}
