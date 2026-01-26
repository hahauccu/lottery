<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>帳號建立完成</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family: Arial, sans-serif; color:#0f172a;">
    <div style="max-width:640px; margin:0 auto; padding:32px 20px;">
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:28px;">
            <h1 style="margin:0 0 12px; font-size:22px;">帳號建立完成</h1>
            <p style="margin:0 0 16px; line-height:1.6;">你好，{{ $organizationName }} 已建立完成，請使用以下資訊登入後台。</p>

            <div style="background:#f1f5f9; border-radius:12px; padding:16px; margin:16px 0;">
                <p style="margin:0 0 8px;">登入帳號：<strong>{{ $email }}</strong></p>
                <p style="margin:0;">登入密碼：<strong>{{ $password }}</strong></p>
            </div>

            <p style="margin:0 0 20px;">登入後建議立即更新密碼。</p>

            <a href="{{ $loginUrl }}" style="display:inline-block; padding:12px 20px; background:#f59e0b; color:#111827; text-decoration:none; border-radius:8px; font-weight:700;">前往登入</a>
        </div>
        <p style="margin:16px 0 0; font-size:12px; color:#94a3b8;">若非本人申請，請忽略此信件。</p>
    </div>
</body>
</html>
