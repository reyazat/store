<?php

namespace App\Repositories\Contracts;

use App\Models\NewsletterSubscriber;

interface NewsletterSubscriberRepositoryInterface
{
    public function findByEmail(string $email): ?NewsletterSubscriber;

    public function create(array $data): NewsletterSubscriber;

    public function reactivate(NewsletterSubscriber $subscriber): NewsletterSubscriber;
}
