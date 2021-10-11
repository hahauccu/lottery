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
Route::post('/create_test', "App\Http\Controllers\LotteryController@create_test");

Route::group(['middleware' => 'adminer'],function(){

  Route::group(['prefix' => 'adminer'],function(){

    Route::get('/login', function () {return view('adminer_login');})->name("userloginpage")->withoutMiddleware('adminer');

    Route::get('/dashborad', function () {return view('user_dashboard');})->name("userdashboard");

   	Route::resource('/lottery_lists', "App\Http\Controllers\LotteryController");

   	Route::resource('/participant_lists', "App\Http\Controllers\ParticipantController");

   	Route::post('/do_login', "App\Http\Controllers\LoginController@do_login")->withoutMiddleware('adminer');

   	Route::resource('/sub_menu', "App\Http\Controllers\SubMenuController");

   });

  Route::get('/lottery/{code}', ["App\Http\Controllers\Front\LotteryController","lotteryBrowse"]);

  Route::get('/lottery/{code?}/{step?}', ["App\Http\Controllers\Front\LotteryController","toLotteryList"]);

  Route::post('/do_lottery/{step?}', ["App\Http\Controllers\Front\LotteryController","doLottery"]);
   	
});


Route::get('/temp', function () {
	return view('temp');
});
