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

		for($couner=0;$couner < $lotterySteps->prize_number ;$couner++)
		{
			//$winnerId = $participants[rand(0,count($participants)-1 )]['id'];
			$winnerId= array_rand($participants,1);
			$winnerArray[]=[
				'step_id' => $lotterySteps->id,
				'participant' => $winnerId
			];
			
		}
		LotteryWinnerLists::insert($winnerArray);
		$winnerListsOfThisDeaw = LotteryWinnerLists::where('step_id',$lotterySteps->id)->get();
		return $winnerListsOfThisDeaw;
	}

	public function getLotterySteps($stepId)
	{
		$lotterySteps = LotterySteps::where('id',$stepId)->with("participants")->first();
		return $lotterySteps;
		
	}

	public function getParticipants($lotterySteps)
	{
		$lotteryListsWithSteps = LotteryLists::with('getLotterySteps')->first();
		$allStepsIdArray = array();
		foreach ($lotteryListsWithSteps['getLotterySteps'] as  $stepsValue) 
		{
			$allStepsIdArray[]= $stepsValue->id;
		}
		$participants = $lotterySteps->participants->keyBy('id')->toArray();
		$lotteryWinnerLists=LotteryWinnerLists::whereIn('step_id',$allStepsIdArray)->get();

		if(!empty($lotteryWinnerLists))
		{
			$lotteryWinnerLists = $lotteryWinnerLists->keyBy('participant')->toArray();

		}
		
		if($lotterySteps->is_repeat_draw != 1)
		{
			$participants = array_diff_key($participants,$lotteryWinnerLists);
		}
		return $participants;
	}
}