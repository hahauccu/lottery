<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryLists extends Model
{
    use HasFactory;

    protected $fillable = ['lottery_name','code','user_id'];
}
