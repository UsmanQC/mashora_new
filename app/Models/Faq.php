<?php

namespace App\Models;

use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question',
        'question_ar',
        'answer',
        'answer_ar',
        'category',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function questionDisplay(): string
    {
        if (app()->getLocale() === 'ar' && filled($this->question_ar)) {
            return (string) $this->question_ar;
        }

        return (string) $this->question;
    }

    public function answerDisplay(): string
    {
        if (app()->getLocale() === 'ar' && filled($this->answer_ar)) {
            return (string) $this->answer_ar;
        }

        return (string) $this->answer;
    }
}
