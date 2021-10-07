<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\LotteryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
class LotteryTest extends TestCase
{
	use RefreshDatabase;
	protected function setUp(): void
	{ 
		parent::setUp();
		$this->artisan('migrate');
    	$this->artisan('db:seed');
	}
    /**
     * A test whitch check if the winner number is equal the needed
     *
     * @return void
     */
    public function test_if_lootery_winner_number_is_currect()
    {
    	$toTestStep = 1; 
    	$lotteryService = new LotteryService();
    	$lotterySteps = $lotteryService->getLotterySteps($toTestStep);
    	$looteryResult = $lotteryService->doLottery($toTestStep);
    	//is winner number currect ?
		$this->assertEquals($lotterySteps['prize_number'],count( $looteryResult) );
    }

    /**
     * Make sure the list for on coming lotter had remove those who has already win in the non repeat step
     *
     * @return void
     */
    public function test_if_winner_can_win_again_in_the_non_repeat_step()
    {
    	$lotteryService = new LotteryService();
    	$step1WinnerIdArray = $lotteryService->doLottery(1)->pluck('participant')->toArray();
    	$toTestStep = 3; 
    	$lotterySteps = $lotteryService->getLotterySteps($toTestStep);
    	$participants = collect($lotteryService->getParticipants($lotterySteps))->pluck('id')->toArray();
    	$step2LooteryResult = $lotteryService->doLottery($toTestStep)->pluck('participant')->toArray();
    	foreach ($step1WinnerIdArray as $step1WinnerValue) 
    	{
    		$this->assertNotContains($step1WinnerValue,$participants);
    		$this->assertNotContains($step1WinnerValue,$step2LooteryResult);
    	}
    }

}
