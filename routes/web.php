<?php

use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DemoLotteryController;
use App\Http\Controllers\LotteryAnalysisController;
use App\Http\Controllers\LotteryFrontendController;
use App\Http\Controllers\LotteryWinnerExportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Support\AnimationStyles;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', ['animationStyles' => AnimationStyles::all()]);
});

Route::get('/testSound', function () {
    return view('testSound');
})->name('testSound');

// Route::get('/home1', function () {
//     return view('home1');
// })->name('home1');

Route::get('/claim/{token}', [ClaimController::class, 'verify'])
    ->name('claim.verify');

// Demo 抽獎（免登入）
Route::get('/demo/lottery', [DemoLotteryController::class, 'landing'])->name('demo.lottery.landing');
Route::get('/demo/lottery/{slug}', [DemoLotteryController::class, 'showStyle'])->name('demo.lottery.style');
Route::post('/demo/lottery/{slug}/configure', [DemoLotteryController::class, 'configure'])->name('demo.lottery.configure');
Route::post('/demo/lottery/{slug}/draw', [DemoLotteryController::class, 'draw'])->name('demo.lottery.draw');
Route::post('/demo/lottery/{slug}/reset', [DemoLotteryController::class, 'reset'])->name('demo.lottery.reset');
Route::post('/demo/lottery/{slug}/ready', [DemoLotteryController::class, 'ready'])->name('demo.lottery.ready');
Route::post('/demo/lottery/{slug}/drawing-state', [DemoLotteryController::class, 'drawingState'])->name('demo.lottery.drawing-state');
Route::post('/demo/lottery/{slug}/switch-ack', [DemoLotteryController::class, 'switchAck'])->name('demo.lottery.switch-ack');

Route::get('/{brandCode}/lottery', [LotteryFrontendController::class, 'show'])
    ->name('lottery.show');
Route::post('/{brandCode}/verify-access', [LotteryFrontendController::class, 'verifyAccess'])
    ->name('lottery.verify-access');
Route::get('/{brandCode}/winners', [LotteryFrontendController::class, 'winners'])
    ->name('lottery.winners');
Route::post('/{brandCode}/winners/verify-email', [LotteryFrontendController::class, 'verifyWinnersEmail'])
    ->name('lottery.winners.verify-email');

// 需要 Operator 存取碼的控制端點
Route::middleware(\App\Http\Middleware\VerifyOperatorAccess::class)->group(function () {
    Route::post('/{brandCode}/draw', [LotteryFrontendController::class, 'draw'])
        ->name('lottery.draw');
    Route::post('/{brandCode}/switch-ack', [LotteryFrontendController::class, 'switchAck'])
        ->name('lottery.switch-ack');
    Route::post('/{brandCode}/ready', [LotteryFrontendController::class, 'ready'])
        ->name('lottery.ready');
    Route::post('/{brandCode}/drawing-state', [LotteryFrontendController::class, 'drawingState'])
        ->name('lottery.drawing-state');
});

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
    Route::get('/lottery-events/{lotteryEvent}/winners/download', [LotteryWinnerExportController::class, 'download'])
        ->name('admin.lottery-winners.download');
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
