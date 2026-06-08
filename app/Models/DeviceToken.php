<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeviceToken extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'userable_type',
        'userable_id',
        'device_token',
        'session_id',
    ];

    public function userable(): MorphTo
    {
        return $this->morphTo();
    }
}
