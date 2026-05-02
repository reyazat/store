<?php

namespace App\Repositories\Contracts;

interface SiteSettingRepositoryInterface
{
    public function getAll(): array;

    public function getByGroup(string $group): array;

    public function updateMany(array $settings): void;

    public function updateFile(string $key, string $storagePath): void;

    public function getFilePath(string $key): ?string;
}
