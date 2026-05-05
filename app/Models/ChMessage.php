<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ChMessage extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'ch_messages';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'from_id',
        'to_id',
        'appointment_id',
        'body',
        'attachment',
        'seen',
        'send_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seen' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChMessage $message): void {
            if ($message->getKey() === null) {
                $message->setAttribute($message->getKeyName(), (string) Str::uuid());
            }
        });
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_id');
    }
}
