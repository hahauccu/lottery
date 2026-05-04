<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrizeAudioSetting extends Model
{
    protected $fillable = [
        'prize_id',
        'sound_key',
        'mode',
        'file_path',
    ];

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }
}
