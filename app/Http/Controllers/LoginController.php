<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function do_login()
    {
    	$remember = 1;
    	if(Auth::attempt(["email"=>$_POST["email"],"password"=>$_POST["password"]],$remember))
    	{
    		return redirect()->intended(route('userdashboard'));
    	}
    	
    	return redirect()->back();
    	
    }
}
