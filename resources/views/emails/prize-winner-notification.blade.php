<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>中獎通知</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #e53935;
            margin: 0;
            font-size: 28px;
        }
        .header .subtitle {
            color: #666;
            margin-top: 5px;
        }
        .prize-info {
            background: linear-gradient(135deg, #fff9c4 0%, #fff59d 100%);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .prize-info .event-name {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .prize-info .prize-name {
            font-size: 24px;
            font-weight: bold;
            color: #d32f2f;
        }
        .winner-info {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .winner-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
        }
        .winner-info-row:last-child {
            border-bottom: none;
        }
        .winner-info-label {
            color: #666;
        }
        .winner-info-value {
            font-weight: 500;
        }
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #fafafa;
            border-radius: 8px;
        }
        .qr-section h3 {
            margin-top: 0;
            color: #333;
        }
        .qr-section img {
            max-width: 200px;
            margin: 15px 0;
        }
        .qr-section .instructions {
            font-size: 14px;
            color: #666;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>恭喜您中獎！</h1>
            <p class="subtitle">您已在抽獎活動中獲得獎項</p>
        </div>

        <div class="prize-info">
            <div class="event-name">{{ $winner->prize->lotteryEvent->name }}</div>
            <div class="prize-name">{{ $winner->prize->name }}</div>
        </div>

        <div class="winner-info">
            <div class="winner-info-row">
                <span class="winner-info-label">中獎者</span>
                <span class="winner-info-value">{{ $winner->employee->name }}</span>
            </div>
            @if($winner->employee->department)
            <div class="winner-info-row">
                <span class="winner-info-label">部門</span>
                <span class="winner-info-value">{{ $winner->employee->department }}</span>
            </div>
            @endif
            <div class="winner-info-row">
                <span class="winner-info-label">中獎序號</span>
                <span class="winner-info-value">#{{ $winner->sequence }}</span>
            </div>
            <div class="winner-info-row">
                <span class="winner-info-label">中獎時間</span>
                <span class="winner-info-value">{{ $winner->won_at->format('Y/m/d H:i') }}</span>
            </div>
        </div>

        <div class="qr-section">
            <h3>領獎 QR Code</h3>
            <img src="{{ $winner->generateQrCodeBase64() }}" alt="領獎 QR Code">
            <p class="instructions">
                請於領獎時出示此 QR Code，<br>
                工作人員掃描後即可完成領獎確認。
            </p>
        </div>

        <div class="footer">
            <p>此信件由系統自動發送，請勿直接回覆。</p>
            <p>{{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>
