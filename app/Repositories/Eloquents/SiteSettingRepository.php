<?php

namespace App\Repositories\Eloquents;

use App\Models\SiteSetting;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class SiteSettingRepository implements SiteSettingRepositoryInterface
{
    public function getAll(): array
    {
        return SiteSetting::all()
            ->mapWithKeys(function (SiteSetting $setting) {
                $value = $setting->value;
                if ($setting->type === 'file' && $value) {
                    $value = Storage::disk('public')->url($value);
                }
                return [$setting->key => $value];
            })
            ->toArray();
    }

    public function getByGroup(string $group): array
    {
        return SiteSetting::where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }

    public function updateMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            SiteSetting::where('key', $key)
                ->where('type', 'text')
                ->update(['value' => $value]);
        }
    }

    public function updateFile(string $key, string $storagePath): void
    {
        SiteSetting::where('key', $key)->update(['value' => $storagePath]);
    }

    public function getFilePath(string $key): ?string
    {
        return SiteSetting::where('key', $key)->value('value');
    }
}
