<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <h2>聯絡我們 - {{ $subject }}</h2>

    <table style="border-collapse: collapse; margin: 16px 0;">
        <tr>
            <td style="padding: 6px 12px; font-weight: bold;">姓名</td>
            <td style="padding: 6px 12px;">{{ $name }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 12px; font-weight: bold;">信箱</td>
            <td style="padding: 6px 12px;">{{ $email }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 12px; font-weight: bold;">主題</td>
            <td style="padding: 6px 12px;">{{ $subject }}</td>
        </tr>
    </table>

    <h3>訊息內容</h3>
    <div style="padding: 12px; background: #f5f5f5; border-radius: 4px; white-space: pre-wrap;">{{ $messageContent }}</div>

    <hr style="margin: 24px 0; border: none; border-top: 1px solid #ddd;">
    <p style="font-size: 12px; color: #999;">此信件由抽獎系統「聯絡我們」表單自動發送</p>
</body>
</html>
