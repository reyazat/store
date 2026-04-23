<?php

namespace App\Actions\Newsletter;

use App\Http\Requests\Newsletter\NewsletterSubscribeRequest;
use App\Models\NewsletterSubscriber;
use App\Services\NewsletterService;

class NewsletterSubscribeAction
{
    public function __construct(
        private readonly NewsletterService $service
    ) {}

    public function execute(NewsletterSubscribeRequest $request): NewsletterSubscriber
    {
        $validated = $request->validated();

        return $this->service->subscribe(
            email: $validated['email'],
            source: $validated['source'] ?? 'home',
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent(),
            user: $request->user(),
        );
    }
}
