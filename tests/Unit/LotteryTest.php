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
    	$defultSteps = 1; 
    	$lotteryService = new LotteryService();
    	$lotterySteps = $lotteryService->getLotterySteps($defultSteps);
    	$looteryResult = $lotteryService->doLottery($defultSteps);
    	//is winner number currect ?
		$this->assertEquals($lotterySteps['prize_number'],count( $looteryResult) );
    }

}
