<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotteryAnalysisRun extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'lottery_event_id',
        'iterations',
        'status',
        'progress',
        'result',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'iterations' => 'integer',
        'progress' => 'integer',
        'result' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function lotteryEvent(): BelongsTo
    {
        return $this->belongsTo(LotteryEvent::class);
    }
}
