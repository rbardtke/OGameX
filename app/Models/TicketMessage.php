<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * TicketMessage model for ticket messages/replies.
 *
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property bool $is_admin_reply
 * @property string $body
 * @property bool $viewed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Ticket $ticket
 * @property-read User $user
 * @method static Builder|TicketMessage newModelQuery()
 * @method static Builder|TicketMessage newQuery()
 * @method static Builder|TicketMessage query()
 * @method static Builder|TicketMessage whereId($value)
 * @method static Builder|TicketMessage whereTicketId($value)
 * @method static Builder|TicketMessage whereUserId($value)
 * @method static Builder|TicketMessage whereIsAdminReply($value)
 * @method static Builder|TicketMessage whereBody($value)
 * @method static Builder|TicketMessage whereViewed($value)
 * @method static Builder|TicketMessage whereCreatedAt($value)
 * @method static Builder|TicketMessage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TicketMessage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'is_admin_reply',
        'body',
        'viewed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_admin_reply' => 'boolean',
        'viewed' => 'boolean',
    ];

    /**
     * Get the ticket that this message belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user that wrote this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
