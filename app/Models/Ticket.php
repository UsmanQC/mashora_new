<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_CLOSED = 'closed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ticket_number',
        'creator_type',
        'creator_id',
        'ticket_category_id',
        'subject',
        'message',
        'status',
        'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function creator(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<TicketCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    /**
     * @return HasMany<TicketReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at');
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function creatorDisplayName(): string
    {
        $creator = $this->creator;

        if ($creator instanceof User) {
            return (string) $creator->name;
        }

        if ($creator instanceof Doctor) {
            return $creator->displayName();
        }

        return __('tickets.unknown_creator');
    }

    public function creatorAudience(): string
    {
        return $this->creator_type === Doctor::class ? 'doctor' : 'patient';
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    public function scopeForCreator(Builder $query, Model $creator): Builder
    {
        return $query
            ->where('creator_type', $creator->getMorphClass())
            ->where('creator_id', $creator->getKey());
    }
}
