<?php

namespace IamApi\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->app->resolving(PermissionRegistrar::class, function (PermissionRegistrar $registrar) {
            // Since each user now has only one organization, use the user's organization_id
            $user = request()?->user();
            if ($user && $user->organization_id) {
                $registrar->setPermissionsTeamId($user->organization_id);
            }
        });
    }
}
