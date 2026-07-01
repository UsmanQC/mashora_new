<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiChatbotBookingCompleteRequest;
use App\Http\Requests\AiChatbotBookingStepRequest;
use App\Services\AiChatbot\AiChatbotBookingFlowService;
use Illuminate\Http\JsonResponse;

class AiChatbotBookingController extends Controller
{
    public function step(AiChatbotBookingStepRequest $request, AiChatbotBookingFlowService $flow): JsonResponse
    {
        $locale = $request->validated('locale') ?? 'ar';

        if (! in_array($locale, ['ar', 'en'], true)) {
            $locale = 'ar';
        }

        /** @var array<string, mixed> $preferences */
        $preferences = $request->validated('preferences') ?? [];

        return response()->json(
            $flow->resolve(
                step: (string) $request->validated('step'),
                preferences: $preferences,
                locale: $locale,
            )
        );
    }

    public function complete(AiChatbotBookingCompleteRequest $request, AiChatbotBookingFlowService $flow): JsonResponse
    {
        $locale = $request->validated('locale') ?? 'ar';

        if (! in_array($locale, ['ar', 'en'], true)) {
            $locale = 'ar';
        }

        /** @var array<string, mixed> $preferences */
        $preferences = $request->validated('preferences');

        return response()->json(
            $flow->complete($preferences, $locale)
        );
    }
}
