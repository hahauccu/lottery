<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return view('welcome');
});


Route::get('/adminer/login', function () {
	return view('adminer_login');
})->name("userloginpage");



Route::post('/create_test', "App\Http\Controllers\LotteryController@create_test");

Route::group(['middleware' => 'adminer', 'prefix' => 'adminer'],function(){
	Route::get('/dashborad', function () {
        return view('user_dashboard');
   	})->name("userdashboard");

   	Route::resource('/lottery_lists', "App\Http\Controllers\LotteryController");

   	Route::resource('/participant_lists', "App\Http\Controllers\ParticipantController");

   	Route::post('/do_login', "App\Http\Controllers\LoginController@do_login")->withoutMiddleware('adminer');

   	Route::resource('/sub_menu', "App\Http\Controllers\SubMenuController");
   	//Route::post('/sub_menu_save', "App\Http\Controllers\SubMenuController@sub_menu_save");
});

// Route::prefix('adminer')->middleware("auth")->group(function () 
// {
// 	Route::get('/dashborad', function () {
//         return view('user_dashboard');
//    	})->name("userdashboard");

//    	Route::post('/do_login', "App\Http\Controllers\LoginController@do_login")->withoutMiddleware("auth");
// });


Route::get('/temp', function () {
	return view('temp');
});
