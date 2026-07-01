<?php

namespace App\Http\Requests;

use App\Services\AiChatbot\AiChatbotBookingFlowService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiChatbotBookingStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'step' => ['required', 'string', Rule::in(AiChatbotBookingFlowService::STEPS)],
            'locale' => ['sometimes', 'string', 'in:ar,en'],
            'preferences' => ['sometimes', 'array'],
            'preferences.degree_id' => ['sometimes', 'string'],
            'preferences.gender_preference' => ['sometimes', 'string', 'in:male,female,both'],
            'preferences.duration_minutes' => ['sometimes', 'string', 'in:15,30,45,60'],
            'preferences.language_preference' => ['sometimes', 'string', 'in:ar,en,both'],
            'preferences.subspecialties' => ['sometimes', 'array'],
            'preferences.subspecialties.*' => ['string'],
            'preferences.doctor_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
