<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'title',
        'message',
        'read_at',
        'userable_type',
        'userable_id',
        'senderable_type',
        'senderable_id',
        'notifiable_type',
        'notifiable_id',
        'action',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function userable(): MorphTo
    {
        return $this->morphTo();
    }
}
