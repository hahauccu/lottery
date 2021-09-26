<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
class UserTest extends TestCase
{
	use RefreshDatabase;

     /**
     * test login fail
     *
     * @return void
     */
    public function test_login_fail()
    {
    	$user = User::factory()->create(["password"=>bcrypt("userpassword")]);
        $user =new UserService($user->email,"fail_password");
        $this->assertEquals($user->verifyLogin(),false);
    }

    /**
     * test login fail
     *
     * @return void
     */
    public function test_login_success()
    {
    	$user = User::factory()->create(["password"=>bcrypt("userpassword")]);
        $user =new UserService($user->email,"userpassword");
        $this->assertEquals($user->verifyLogin(),true);
    }

}
