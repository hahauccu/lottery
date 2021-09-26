<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantDetails extends Model
{
    use HasFactory;
    protected $fillable = ['name','phone','email','is_visible','order','participant_lists_code'];
}
