<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
    	//create user 
    	//\App\Models\User::factory(1)->create(
    	$faker = Faker::create();
        \App\Models\User::create(
        	[
        		"id"=>999 , 
        		"name"=>$faker->name, 
        		"email"=>"murphy73@example.net" , 
        		"password"=>bcrypt("userpassword")
        	]
        );

        //create lottery 
        \App\Models\LotteryLists::create(
        	[
        		"id"=>99,
        		"lottery_name"=>"moon fastivlal lottery",
        		"code" => "SDvvdyuwqd",
        		"user_id" => 999,

        	]
        );

        \App\Models\LotterySteps::truncate();
        //create lottery 
        $lotterySteps=
        [
    		[
    			'step_name'=>'ten cell phone',
        		'is_visible'=>1,
        		'order'=>1,
        		'prize_number'=>10,
        		'is_repeat_draw'=>1,
        		'participate_list'=>99,
        		'lottery_code'=>'SDvvdyuwqd',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
    		],
    		[
    			'step_name'=>'twenty cell phone',
        		'is_visible'=>1,
        		'order'=>1,
        		'prize_number'=>20,
        		'is_repeat_draw'=>1,
        		'participate_list'=>99,
        		'lottery_code'=>'SDvvdyuwqd',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
    		],
            [
                'step_name'=>'a thousand dollor',
                'is_visible'=>1,
                'order'=>1,
                'prize_number'=>10,
                'is_repeat_draw'=>0,
                'participate_list'=>99,
                'lottery_code'=>'SDvvdyuwqd',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ];
        \App\Models\LotterySteps::insert($lotterySteps);

        \App\Models\ParticipantLists::create(
        	[
        		'name'=>'all employee',
        		'code'=>'KdaszUDSkd',
        		'id'=>99,
        		'user_id'=>999,
        	]
        );

        \App\Models\ParticipantDetails::Factory(50)->create(
        	[
        		'participant_lists_code'=>'KdaszUDSkd',
        		'is_visible'=>1,
        		'order'=>1,
        	]
        );
         
    }
}
