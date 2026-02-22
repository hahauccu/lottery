<?php

use App\Http\Controllers\ClaimController;
use App\Http\Controllers\LotteryAnalysisController;
use App\Http\Controllers\LotteryFrontendController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/testSound', function () {
    return view('testSound');
})->name('testSound');

// Route::get('/home1', function () {
//     return view('home1');
// })->name('home1');

Route::get('/claim/{token}', [ClaimController::class, 'verify'])
    ->name('claim.verify');

Route::get('/{brandCode}/lottery', [LotteryFrontendController::class, 'show'])
    ->name('lottery.show');
Route::post('/{brandCode}/draw', [LotteryFrontendController::class, 'draw'])
    ->name('lottery.draw');
Route::get('/{brandCode}/winners', [LotteryFrontendController::class, 'winners'])
    ->name('lottery.winners');
Route::post('/{brandCode}/switch-ack', [LotteryFrontendController::class, 'switchAck'])
    ->name('lottery.switch-ack');
Route::post('/{brandCode}/ready', [LotteryFrontendController::class, 'ready'])
    ->name('lottery.ready');
Route::post('/{brandCode}/drawing-state', [LotteryFrontendController::class, 'drawingState'])
    ->name('lottery.drawing-state');
Route::post('/{brandCode}/danmaku', [LotteryFrontendController::class, 'sendDanmaku'])
    ->name('lottery.danmaku')
    ->middleware('throttle:danmaku');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/analysis-runs/{analysisRun}/download', [LotteryAnalysisController::class, 'download'])
        ->name('admin.lottery-analysis.download');
    Route::post('/api/claims/verify', [ClaimController::class, 'apiVerify'])
        ->name('api.claims.verify');

    // 付款路由（需要登入）
    Route::post('/payment/checkout', [PaymentController::class, 'checkout'])
        ->name('payment.checkout');
});

// 綠界 notify（Server to Server，不需 auth，CSRF 已在 bootstrap/app.php 排除）
Route::post('/payment/ecpay/notify', [PaymentController::class, 'notify'])
    ->name('payment.ecpay.notify');

// 綠界 result（用戶返回頁面，CSRF 已在 bootstrap/app.php 排除）
Route::match(['get', 'post'], '/payment/ecpay/result', [PaymentController::class, 'result'])
    ->name('payment.ecpay.result');

require __DIR__.'/auth.php';
