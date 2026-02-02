<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Services\EcpayLogger;
use App\Services\EcpayService;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private EcpayService $ecpayService
    ) {}

    /**
     * 發起付款（從訂閱管理頁面呼叫）
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        // 取得組織（從 session 或 request）
        $organization = Filament::getTenant();
        if (! $organization) {
            return back()->with('error', '找不到組織資訊');
        }

        $plan = SubscriptionPlan::find($request->plan_id);
        if (! $plan || ! $plan->is_active) {
            return back()->with('error', '找不到方案或方案已停用');
        }

        // 檢查員工數
        $employeeCount = $organization->employees()->count();
        if ($employeeCount > $plan->max_employees) {
            return back()->with('error', "員工數超過方案上限（目前 {$employeeCount} 人，方案上限 {$plan->max_employees} 人）");
        }

        // 建立付款單
        $result = $this->ecpayService->createPayment($organization, $plan);

        // 記錄日誌
        EcpayLogger::log('checkout', [
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'payment_id' => $result['payment']->id,
            'merchant_trade_no' => $result['params']['MerchantTradeNo'],
            'amount' => $result['params']['TotalAmount'],
        ]);

        // 返回 checkout 頁面（自動提交表單）
        return view('payment.checkout', [
            'formHtml' => $result['form_html'],
            'plan' => $plan,
            'payment' => $result['payment'],
        ]);
    }

    /**
     * 綠界背景通知（Server to Server）
     */
    public function notify(Request $request)
    {
        $data = $request->all();

        // 記錄完整 request
        EcpayLogger::log('notify', [
            'request' => $data,
            'ip' => $request->ip(),
        ]);

        // 處理回調
        $result = $this->ecpayService->handleCallback($data);

        // 記錄處理結果
        EcpayLogger::log('notify_result', [
            'merchant_trade_no' => $data['MerchantTradeNo'] ?? '',
            'success' => $result['success'],
            'message' => $result['message'],
        ]);

        // 回傳 "1|OK" 給綠界
        return response('1|OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * 綠界前台返回（用戶跳轉回來）
     */
    public function result(Request $request)
    {
        $data = $request->all();

        // 記錄 request
        EcpayLogger::log('result', [
            'request' => $data,
            'ip' => $request->ip(),
        ]);

        $merchantTradeNo = $data['MerchantTradeNo'] ?? '';
        $payment = Payment::where('merchant_trade_no', $merchantTradeNo)->first();

        $success = false;
        $message = '付款處理中';

        if ($payment) {
            if ($payment->isPaid()) {
                $success = true;
                $message = '付款成功！訂閱已啟用。';
            } elseif ($payment->isFailed()) {
                $message = '付款失敗，請重新嘗試。';
            } else {
                // pending 狀態，可能 notify 還沒到
                $message = '付款處理中，請稍後重新整理頁面確認。';
            }
        } else {
            $message = '找不到付款記錄。';
        }

        return view('payment.result', [
            'success' => $success,
            'message' => $message,
            'payment' => $payment,
            'ecpayData' => $data,
        ]);
    }
}
