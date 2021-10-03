<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Services\CrudService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\LotteryLists;
use App\Models\LotterySteps;
use App\Models\LotteryService;

class LotteryController
{
    public function lotteryBrowse($code)
    {
        $browseData = LotteryLists::where("code",$code)
                                  ->where("user_id",Auth::id())
                                  ->with("getLotterySteps")
                                  ->first()
                                  ->toArray();
        return view("front/lottery_browse",
            [
                'browseData'=>$browseData,
            ]
        );
    }

    public function toLotteryList($code,$step)
    {
        $lotteryData = LotterySteps::where("lottery_code",$code)
                                   ->where("id",$step)
                                   ->with("participants")
                                   ->first()
                                   ->toArray();

        
        return view("front/to_lottery_list",
            [
                'lotteryData'=>$lotteryData,
            ]
        );
    }

    // public function toLotteryList($step)
    // {
    //   $lotteryData = LotterySteps::where("lottery_code",$code)
    //                               ->where("id",$step)
    //                               ->with("participants")
    //                               ->first()
    //                               ->toArray();
          
    // }
}
