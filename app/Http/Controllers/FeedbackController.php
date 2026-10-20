<?php

namespace App\Http\Controllers;

use App\Mail\FeedbackReceivedMail;
use App\Models\Feedback;
use App\Support\AnimationStyles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $source = $request->query('from');
        $style = $request->query('style');

        return view('feedback', [
            'categories' => Feedback::CATEGORIES,
            'styles' => AnimationStyles::labels(),
            'sourcePath' => is_string($source) && in_array($source, Feedback::sourcePaths(), true) ? $source : null,
            'animationStyle' => is_string($style) && array_key_exists($style, AnimationStyles::labels()) ? $style : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(Feedback::CATEGORIES))],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'email' => ['nullable', 'email', 'max:255'],
            'source_path' => ['nullable', Rule::in(Feedback::sourcePaths())],
            'animation_style' => ['nullable', Rule::in(array_keys(AnimationStyles::labels()))],
            'viewport_width' => ['nullable', 'integer', 'min:1', 'max:16384'],
            'viewport_height' => ['nullable', 'integer', 'min:1', 'max:16384'],
            'website' => ['nullable', 'string', 'max:0'],
        ], [
            'message.min' => '請至少填寫 10 個字，描述你遇到的情況或建議。',
            'message.max' => '內容請控制在 3000 個字以內。',
            'email.email' => '請填寫有效的回覆信箱，或留空。',
        ]);
        unset($data['website']);

        // 先保存再通知，避免寄信失敗造成使用者回饋遺失。
        $feedback = Feedback::create($data);
        if ($recipient = config('feedback.notification_email')) {
            try {
                Mail::to($recipient)->send(new FeedbackReceivedMail($feedback));
                $feedback->update(['notified_at' => now()]);
            } catch (\Throwable $error) {
                Log::warning('Feedback notification failed', [
                    'feedback_id' => $feedback->id,
                    'exception' => $error::class,
                ]);
            }
        }

        return redirect()->route('feedback.index')->with('status', '已收到你的回饋，謝謝你幫助我們改善抽獎體驗！');
    }
}
