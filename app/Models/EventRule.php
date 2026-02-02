<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRule extends Model
{
    public const TYPE_INCLUDE_EMPLOYEE = 'include_employee';

    public const TYPE_INCLUDE_GROUP = 'include_group';

    public const TYPE_EXCLUDE_EMPLOYEE = 'exclude_employee';

    public const TYPE_EXCLUDE_GROUP = 'exclude_group';

    protected $fillable = [
        'lottery_event_id',
        'type',
        'ref_id',
    ];

    public function lotteryEvent(): BelongsTo
    {
        return $this->belongsTo(LotteryEvent::class);
    }
}
