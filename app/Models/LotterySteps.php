<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotterySteps extends Model
{
    use HasFactory;
    protected $fillable = ['step_name','is_visible','order','is_multiple','prize_number','is_repeat_draw','participate_list','lottery_code'];

    public function participants()
    {
    	return $this->hasManyThrough(
    		'App\Models\ParticipantDetails',
    		'App\Models\ParticipantLists',
    		'id',
    		'participant_lists_code',
    		'participate_list',
    		'code')->where('is_visible',1);
    }
}
