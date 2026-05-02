<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\SiteSetting\SiteSettingIndexAction;
use App\Actions\SiteSetting\SiteSettingUpdateAction;
use App\Actions\SiteSetting\SiteSettingUploadFileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SiteSetting\SiteSettingUpdateRequest;
use App\Http\Requests\SiteSetting\SiteSettingUploadFileRequest;
use Illuminate\Http\JsonResponse;

class SiteSettingController extends Controller
{
    public function __construct(
        private readonly SiteSettingIndexAction      $indexAction,
        private readonly SiteSettingUpdateAction     $updateAction,
        private readonly SiteSettingUploadFileAction $uploadFileAction,
    ) {}

    public function index(): JsonResponse
    {
        $settings = $this->indexAction->execute();

        return response()->json([
            'success' => true,
            'data'    => $settings,
        ]);
    }

    public function update(SiteSettingUpdateRequest $request): JsonResponse
    {
        $settings = $this->updateAction->execute($request);

        return response()->json([
            'success' => true,
            'message' => 'تنظیمات سایت به‌روزرسانی شد',
            'data'    => $settings,
        ]);
    }

    public function uploadFile(SiteSettingUploadFileRequest $request): JsonResponse
    {
        $url = $this->uploadFileAction->execute(
            $request->validated()['key'],
            $request->file('file'),
        );

        return response()->json([
            'success' => true,
            'message' => 'فایل با موفقیت آپلود شد',
            'data'    => ['url' => $url],
        ]);
    }
}
