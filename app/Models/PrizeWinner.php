<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrizeWinner extends Model
{
    protected $fillable = [
        'prize_id',
        'employee_id',
        'sequence',
        'won_at',
    ];

    protected $casts = [
        'won_at' => 'datetime',
    ];

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

