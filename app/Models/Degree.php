<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Degree extends Model
{
    protected $table = 'degrees';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'title_ar',
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
}
