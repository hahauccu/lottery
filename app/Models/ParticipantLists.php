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
    	return $this->hasMany('App\Models\ParticipantDetails','participant_lists_code','code');
    }
}
