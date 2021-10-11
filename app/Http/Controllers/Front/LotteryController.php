<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Services\CrudService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\LotteryLists;
use App\Models\LotterySteps;
use App\Services\LotteryService;


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
      $lotteryService = new LotteryService;
      $lotteryData = LotterySteps::where("lottery_code",$code)
                                 ->where("id",$step)
                                 ->with("participants")
                                 ->with("winners")
                                 ->first();
      //get participants
      $participants = $lotteryService->getParticipants($lotteryData);
      //reindex the key
      $participants= array_values($participants);
      $winners = $lotteryData->winners->keyBy("participant")->toArray();
      $lotteryData= $lotteryData->toArray();
      $lotteryData['participants'] =$participants;
      
        
      return view("front/to_lottery_list",
          [
              'lotteryData'=>$lotteryData,
              'winners'=>$winners,

          ]
      );
    }

    public function doLottery($step)
    {
      $lotteryService = new LotteryService;
      $lotteryService->doLottery($step);
      return 1;
    }
}
