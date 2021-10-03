<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryWinnerLists extends Model
{
    use HasFactory;
    protected $fillable = ['step_id','user_id'];
}
