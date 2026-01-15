<?php

use App\Http\Controllers\LotteryFrontendController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('', 204);
});

Route::get('/{brandCode}/lottery', [LotteryFrontendController::class, 'show'])
    ->name('lottery.show');
Route::post('/{brandCode}/draw', [LotteryFrontendController::class, 'draw'])
    ->name('lottery.draw');
Route::get('/{brandCode}/winners', [LotteryFrontendController::class, 'winners'])
    ->name('lottery.winners');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
