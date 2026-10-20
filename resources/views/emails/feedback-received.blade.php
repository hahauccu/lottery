<!DOCTYPE html>
<html lang="zh-Hant">
<body>
    <h2>意見回饋 #{{ $feedback->id }}：{{ \App\Models\Feedback::CATEGORIES[$feedback->category] }}</h2>
    <p>回覆信箱：{{ $feedback->email ?: '未提供' }}</p>
    <p>來源頁面：{{ $feedback->source_path ?: '未提供' }}</p>
    <p>動畫：{{ \App\Support\AnimationStyles::labels()[$feedback->animation_style] ?? '未指定' }}</p>
    <p>視窗尺寸：{{ $feedback->viewport_width ?: '?' }} × {{ $feedback->viewport_height ?: '?' }}</p>
    <div style="white-space: pre-wrap;">{{ $feedback->message }}</div>
    <p>請至後台「系統管理 → 意見回饋」查看與更新處理狀態。</p>
</body>
</html>
