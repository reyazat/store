<?php

namespace App\Actions\SiteSetting;

use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Services\SiteLogoService;
use Illuminate\Http\UploadedFile;

class SiteSettingUploadFileAction
{
    public function __construct(
        private readonly SiteSettingRepositoryInterface $repository,
        private readonly SiteLogoService $logoService,
    ) {}

    public function execute(string $key, UploadedFile $file): string
    {
        $oldPath = $this->repository->getFilePath($key);
        $this->logoService->delete($oldPath);

        $newPath = $this->logoService->store($file, $key);
        $this->repository->updateFile($key, $newPath);

        return $this->logoService->getUrl($newPath) ?? '';
    }
}
