<?php

namespace IamApi\Repositories\Eloquent;

use IamApi\Models\Organization;
use IamApi\Models\OrganizationDomain;
use IamApi\Repositories\Contracts\OrganizationDomainRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentOrganizationDomainRepository extends BaseRepository implements OrganizationDomainRepositoryInterface
{
    public function __construct(OrganizationDomain $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate(array $data, array $conditions = []): Model
    {
        if (empty($conditions)) {
            return $this->create($data);
        }

        $domain = $this->model->query()->where($conditions)->first();
        if ($domain) {
            $this->update($domain, $data);
            return $domain;
        }

        return $this->create(array_merge($conditions, $data));
    }

    public function updateOrCreate(array $conditions, array $data): OrganizationDomain
    {
        return $this->model->query()->updateOrCreate($conditions, $data);
    }

    public function bulkInsert(array $data): bool
    {
        return $this->model->query()->insert($data);
    }

    public function bulkUpdate(array $data, string $key = 'id'): bool
    {
        foreach ($data as $item) {
            if (isset($item[$key])) {
                $this->model->query()->where($key, $item[$key])->update($item);
            }
        }
        return true;
    }

    public function softDelete(Model $model): bool
    {
        return $this->delete($model);
    }

    public function restore(Model $model): bool
    {
        return false;
    }

    public function forceDelete(Model $model): bool
    {
        return $this->delete($model);
    }

    public function findByDomain(string $domain): ?OrganizationDomain
    {
        return $this->model->query()->where('domain', $domain)->first();
    }

    public function findByOrganization(Organization $organization): Collection
    {
        return $this->model->query()->where('organization_id', $organization->id)->get();
    }

    public function findVerifiedDomains(): Collection
    {
        return $this->model->query()->whereNotNull('verified_at')->get();
    }

    public function findUnverifiedDomains(): Collection
    {
        return $this->model->query()->whereNull('verified_at')->get();
    }

    public function createForOrganization(Organization $organization, string $domain): OrganizationDomain
    {
        return $this->model->query()->create([
            'organization_id' => $organization->id,
            'domain' => $domain,
            'verified_at' => null
        ]);
    }

    public function verifyDomain(OrganizationDomain $domain): bool
    {
        return $this->update($domain, ['verified_at' => now()]);
    }

    public function isVerified(OrganizationDomain $domain): bool
    {
        return !is_null($domain->verified_at);
    }

    public function existsForOrganization(Organization $organization, string $domain): bool
    {
        return $this->model->query()->where('organization_id', $organization->id)
            ->where('domain', $domain)
            ->exists();
    }

    public function deleteFromOrganization(Organization $organization, string $domain): bool
    {
        return $this->model->query()->where('organization_id', $organization->id)
            ->where('domain', $domain)
            ->delete() > 0;
    }

    public function findOrganizationByDomain(string $domain): ?Organization
    {
        $organizationDomain = $this->findByDomain($domain);
        return $organizationDomain ? $organizationDomain->organization : null;
    }

    public function isEmailDomainAllowed(Organization $organization, string $email): bool
    {
        $emailDomain = substr(strrchr($email, "@"), 1);

        return $this->model->query()->where('organization_id', $organization->id)
            ->where('domain', $emailDomain)
            ->whereNotNull('verified_at')
            ->exists();
    }
}
