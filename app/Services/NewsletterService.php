<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface;

class NewsletterService
{
    public function __construct(
        private readonly NewsletterSubscriberRepositoryInterface $repository
    ) {}

    public function subscribe(
        string $email,
        ?string $source = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?User $user = null,
    ): NewsletterSubscriber {
        $existing = $this->repository->findByEmail($email);

        if ($existing) {
            return $existing->is_active
                ? $existing
                : $this->repository->reactivate($existing);
        }

        return $this->repository->create([
            'email'      => $email,
            'user_id'    => $user?->id,
            'source'     => $source,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'is_active'  => true,
        ]);
    }
}
