<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantLists extends Model
{
    use HasFactory;
    protected $fillable = ['name','user_id','code'];

    public function getParticipantDetails()
    {
    	return $this->hasOneThrough(
    		'App\Models\ParticipantLists',
    		'App\Models\ParticipantDetails',
    		'id',
    		'participant_lists_code',
    		'participate_list',
    		'code',
    	);
    }
}
