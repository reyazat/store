<?php

namespace App\Http\Controllers\API;

use App\Actions\Newsletter\NewsletterSubscribeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Newsletter\NewsletterSubscribeRequest;
use App\Http\Resources\Newsletter\NewsletterSubscriberResource;
use Illuminate\Http\JsonResponse;

class NewsletterController extends Controller
{
    public function __construct(
        private readonly NewsletterSubscribeAction $subscribeAction
    ) {}

    public function subscribe(NewsletterSubscribeRequest $request): JsonResponse
    {
        $subscriber = $this->subscribeAction->execute($request);

        return response()->json([
            'success' => true,
            'message' => 'عضویت شما در خبرنامه با موفقیت ثبت شد',
            'data'    => new NewsletterSubscriberResource($subscriber),
        ], 201);
    }
}
