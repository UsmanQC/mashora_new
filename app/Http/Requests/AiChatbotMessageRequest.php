<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiChatbotMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $maxLength = (int) config('ai_chatbot.max_message_length', 2000);

        return [
            'message' => ['required', 'string', 'max:'.$maxLength],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'message' => __('ai_chatbot.message_field'),
        ];
    }
}
