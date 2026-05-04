<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoLotteryTemplateReport extends Model
{
    protected $fillable = [
        'result_text',
        'author_name',
        'comment',
        'is_public',
        'is_hidden',
        'ip_hash',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DemoLotteryTemplate::class, 'demo_lottery_template_id');
    }
}
