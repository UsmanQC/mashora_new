<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\SoftDeletes;

class Thought extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'thoughts';

    protected $fillable = [
        'description_en',
        'description_ar',
        'auth_en',
        'auth_ar',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    /**
     * Display content in as language selected // description
     */
    public function getDescriptionAttribute(): ?string
    {
        return App::getLocale() === 'ar' ? $this->description_ar : $this->description_en;
    }

    /**
     * Display author name as language selected // author
     */
    public function getAuthorAttribute(): ?string
    {
        return App::getLocale() === 'ar' ? $this->auth_ar : $this->auth_en;
    }
}
