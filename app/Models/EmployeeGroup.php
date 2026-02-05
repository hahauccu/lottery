<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmployeeGroup extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'system_key',
        'lottery_event_id',
        'prize_id',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function lotteryEvent(): BelongsTo
    {
        return $this->belongsTo(LotteryEvent::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_group_employee')->withTimestamps();
    }

    public function isSystemGroup(): bool
    {
        return $this->system_key !== null;
    }
}
