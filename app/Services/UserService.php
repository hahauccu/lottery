<?php 

namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
class UserService
{
	private $email;
	private $password;

	public function __construct($email,$password)
	{
		$this->email = $email;
		$this->password = $password;
	}

	public function verifyLogin()
	{
		
		//login success 
		// Hash::check($userdata->password,$this->password)
		if(Auth::attempt(["email"=>$this->email,"password"=>$this->password]))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}