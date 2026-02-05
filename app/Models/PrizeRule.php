<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrizeRule extends Model
{
    public const TYPE_INCLUDE_EMPLOYEE = 'include_employee';

    public const TYPE_INCLUDE_GROUP = 'include_group';

    public const TYPE_EXCLUDE_EMPLOYEE = 'exclude_employee';

    public const TYPE_EXCLUDE_GROUP = 'exclude_group';

    /**
     * @deprecated Use system groups (exclude_group with event_winners/prize_winners) instead
     */
    public const TYPE_EXCLUDE_PRIZE_WINNERS = 'exclude_prize_winners';

    protected $fillable = [
        'prize_id',
        'type',
        'ref_id',
    ];

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }
}
