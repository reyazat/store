<?php

namespace App\Actions\Organization;

use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\OrganizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates a new organization and optionally creates a new user as its owner,
 * or assigns an existing user as owner.
 *
 * Supports two owner modes:
 *  - existing: owner_id is provided → existing user is assigned as owner
 *  - new:      owner_data is provided → new user is created then assigned as owner
 */
class CreateStoreWithOwnerAction
{
    public function __construct(
        private readonly OrganizationService $organizationService,
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Assign an owner to an already existing organization.
     * Supports both existing user (owner_id) and new user (new_owner array) modes.
     */
    public function assignOwnerToExisting(Organization $organization, ?int $ownerId, ?array $newOwnerData): array
    {
        return DB::transaction(function () use ($organization, $ownerId, $newOwnerData) {
            if ($ownerId !== null) {
                $owner = $this->userRepository->findOneBy('id', $ownerId);

                if (!$owner) {
                    throw new \RuntimeException('Specified owner user not found.');
                }

                if ($owner->is_super_admin) {
                    throw new \RuntimeException('Super admin cannot be assigned as store owner.');
                }

                if ($owner->organization_id !== null && $owner->organization_id !== $organization->id) {
                    $previousOrg = Organization::find($owner->organization_id);
                    if ($previousOrg) {
                        $this->organizationRepository->removeMember($previousOrg, $owner);
                    }
                }

                if ($owner->organization_id === $organization->id) {
                    $this->organizationRepository->updateMemberRole($organization, $owner, 'owner');
                } else {
                    $this->organizationRepository->addMember($organization, $owner, 'owner');
                }

                return ['owner' => $owner->fresh(), 'owner_created' => false];
            }

            if (!empty($newOwnerData)) {
                if ($this->userRepository->findByEmail($newOwnerData['email'])) {
                    throw new \RuntimeException('A user with this email already exists. Use "existing owner" mode instead.');
                }

                /** @var User $owner */
                $owner = $this->userRepository->create([
                    'name'                 => $newOwnerData['name'],
                    'email'                => $newOwnerData['email'],
                    'phone'                => $newOwnerData['phone'] ?? null,
                    'password'             => $newOwnerData['password'],
                    'status'               => 'active',
                    'must_change_password' => true,
                    'password_changed_at'  => now(),
                    'password_expires_at'  => now()->addDays(config('iam.password.expiry_days', 90)),
                ]);

                $this->organizationRepository->addMember($organization, $owner, 'owner');

                return ['owner' => $owner->fresh(), 'owner_created' => true];
            }

            throw new \RuntimeException('Either owner_id or new_owner must be provided.');
        });
    }

    /**
     * @param array{
     *   name: string,
     *   description?: string|null,
     *   status?: string,
     *   owner_id?: int|null,
     *   new_owner?: array{name: string, email: string, password: string, phone?: string}|null
     * } $data
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $orgData = array_filter(
                array_intersect_key($data, array_flip(['name', 'description', 'logo', 'status'])),
                fn($v) => $v !== null
            );

            /** @var Organization $organization */
            $organization = $this->organizationRepository->create($orgData);

            // Dispatch event to create default roles/permissions before addMember
            event(new \IamApi\Events\OrganizationCreated($organization));

            // Determine owner
            if (!empty($data['owner_id'])) {
                // Mode: existing user
                /** @var User $owner */
                $owner = $this->userRepository->findOneBy('id', $data['owner_id']);

                if (!$owner) {
                    throw new \RuntimeException('Specified owner user not found.');
                }

                if ($owner->is_super_admin) {
                    throw new \RuntimeException('Super admin cannot be assigned as store owner.');
                }

                // If already member of THIS org, just update role; otherwise add
                if ($owner->organization_id === $organization->id) {
                    $this->organizationRepository->updateMemberRole($organization, $owner, 'owner');
                } else {
                    // Transfer from another org if needed
                    if ($owner->organization_id !== null) {
                        $previousOrg = Organization::find($owner->organization_id);
                        if ($previousOrg) {
                            $this->organizationRepository->removeMember($previousOrg, $owner);
                        }
                    }
                    $this->organizationRepository->addMember($organization, $owner, 'owner');
                }

                return ['organization' => $organization->fresh(), 'owner' => $owner->fresh(), 'owner_created' => false];
            }

            if (!empty($data['new_owner'])) {
                // Mode: create new user as owner
                $ownerData = $data['new_owner'];

                if ($this->userRepository->findByEmail($ownerData['email'])) {
                    throw new \RuntimeException('A user with this email already exists. Use "existing owner" mode instead.');
                }

                /** @var User $owner */
                $owner = $this->userRepository->create([
                    'name'                 => $ownerData['name'],
                    'email'                => $ownerData['email'],
                    'phone'                => $ownerData['phone'] ?? null,
                    'password'             => $ownerData['password'],
                    'status'               => 'active',
                    'must_change_password' => true,
                    'password_changed_at'  => now(),
                    'password_expires_at'  => now()->addDays(config('iam.password.expiry_days', 90)),
                ]);

                $this->organizationRepository->addMember($organization, $owner, 'owner');

                return ['organization' => $organization->fresh(), 'owner' => $owner->fresh(), 'owner_created' => true];
            }

            // No owner provided: store created without an owner
            return ['organization' => $organization->fresh(), 'owner' => null, 'owner_created' => false];
        });
    }
}
