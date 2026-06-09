<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TicketReply extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'ticket_id',
        'author_type',
        'author_id',
        'message',
    ];

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    public function authorDisplayName(): string
    {
        $author = $this->author;

        if ($author instanceof Admin) {
            return (string) ($author->name ?? __('tickets.support_team'));
        }

        if ($author instanceof User) {
            return (string) $author->name;
        }

        if ($author instanceof Doctor) {
            return $author->displayName();
        }

        return __('tickets.unknown_creator');
    }

    public function isFromAdmin(): bool
    {
        return $this->author_type === Admin::class;
    }
}
