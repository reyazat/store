<?php

namespace App\Repositories\Eloquents;

use App\Models\NewsletterSubscriber;
use App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface;

class NewsletterSubscriberRepository implements NewsletterSubscriberRepositoryInterface
{
    public function findByEmail(string $email): ?NewsletterSubscriber
    {
        return NewsletterSubscriber::where('email', $email)->first();
    }

    public function create(array $data): NewsletterSubscriber
    {
        return NewsletterSubscriber::create($data);
    }

    public function reactivate(NewsletterSubscriber $subscriber): NewsletterSubscriber
    {
        $subscriber->update([
            'is_active'       => true,
            'unsubscribed_at' => null,
        ]);

        return $subscriber->fresh();
    }
}
