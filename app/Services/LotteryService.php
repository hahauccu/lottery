<?php 

namespace App\Services;
use App\Models\LotteryLists;
use App\Models\LotterySteps;
use App\Models\LotteryWinnerLists;
class LotteryService
{
	private $modleName;
	

	public function __construct()
	{
		
	}

	public function doLottery($stepId)
	{
		$lotterySteps = $this->getLotterySteps($stepId);
		$participants = $this->getParticipants($lotterySteps);
		$winnerArray = array();
		$couner =0;

		for($couner=0;$couner < $lotterySteps['prize_number'] ;$couner++)
		{
			$winnerId = $participants[rand(0,count($participants)-1 )]['id'];
			$winnerArray[]=[
				'step_id' => $lotterySteps['id'],
				'user_id' => $winnerId
			];
			
		}
		LotteryWinnerLists::insert($winnerArray);
		$winnerListsOfThisDeaw = LotteryWinnerLists::where('step_id',$lotterySteps['id'])->get();
		return $winnerListsOfThisDeaw;
	}

	public function getLotterySteps($stepId)
	{
		$lotterySteps = LotterySteps::where('id',$stepId)->with("participants")->first()->toArray();
		return $lotterySteps;
		
	}

	public function getParticipants($lotterySteps)
	{
		$participants = $lotterySteps['participants'];
		return $participants;
	}
	
}