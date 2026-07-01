<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'system_prompt',
        'allowed_topics',
        'blocked_topics',
        'estimated_cost_cents',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allowed_topics' => 'array',
            'blocked_topics' => 'array',
            'estimated_cost_cents' => 'integer',
        ];
    }

    public static function current(): self
    {
        $settings = self::query()->first();

        if ($settings instanceof self) {
            return $settings;
        }

        return self::query()->create([
            'is_active' => true,
            'system_prompt' => (string) config('ai_chatbot.system_prompt'),
            'allowed_topics' => [
                'booking appointments',
                'therapist recommendations',
                'platform FAQs',
                'mental health support information',
                'Awaan services',
            ],
            'blocked_topics' => [
                'cryptocurrency',
                'news',
                'sports',
                'programming',
                'politics',
            ],
        ]);
    }

    public function effectiveSystemPrompt(): string
    {
        $base = trim((string) ($this->system_prompt ?: config('ai_chatbot.system_prompt')));

        $allowed = collect($this->allowed_topics ?? [])
            ->filter()
            ->implode(', ');

        $blocked = collect($this->blocked_topics ?? [])
            ->filter()
            ->implode(', ');

        $rules = trim(<<<PROMPT
You are the Awaan (Consulta) AI assistant for a mental health platform.

Allowed topics only: {$allowed}.
Politely refuse unrelated topics such as: {$blocked}.
You may help with booking guidance, therapist recommendations, and FAQs.
Never provide medical diagnosis or prescribe medication.
If the user needs urgent help, encourage contacting a licensed specialist or emergency services.
PROMPT);

        return $base."\n\n".$rules;
    }
}
