<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SanitizesChatbotBookingPreferences;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiChatbotBookingCompleteRequest extends FormRequest
{
    use SanitizesChatbotBookingPreferences;

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
            'locale' => ['sometimes', 'string', 'in:ar,en'],
            'preferences' => ['required', 'array'],
            'preferences.degree_id' => ['sometimes', 'string'],
            'preferences.gender_preference' => ['sometimes', 'string', Rule::in(['male', 'female', 'both'])],
            'preferences.duration_minutes' => ['sometimes', 'string', Rule::in(['15', '30', '45', '60'])],
            'preferences.language_preference' => ['sometimes', 'string', Rule::in(['ar', 'en', 'both'])],
            'preferences.subspecialties' => ['sometimes', 'array'],
            'preferences.subspecialties.*' => ['string'],
            'preferences.doctor_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
