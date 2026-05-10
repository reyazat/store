<?php

namespace App\Http\Controllers\API;

use App\Actions\Faq\FaqIndexAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Faq\FaqResource;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function __construct(
        private readonly FaqIndexAction $indexAction,
    ) {}

    public function index(): JsonResponse
    {
        $faqs = $this->indexAction->execute(activeOnly: true);

        return response()->json([
            'success' => true,
            'data'    => FaqResource::collection($faqs),
        ]);
    }
}
