<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} - کد تأیید</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Tahoma', 'Arial', sans-serif;
            background-color: #f4f6f9;
            color: #333;
            direction: rtl;
        }
        .wrapper {
            max-width: 560px;
            margin: 32px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .header {
            background: linear-gradient(135deg, #1a56db, #0e3fa8);
            padding: 28px 32px;
            text-align: center;
        }
        .header h1 {
            color: #fff;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: -0.5px;
        }
        .header p {
            color: rgba(255,255,255,0.85);
            font-size: 13px;
            margin-top: 6px;
        }
        .body {
            padding: 32px;
        }
        .body p {
            font-size: 15px;
            line-height: 1.8;
            color: #444;
            margin-bottom: 20px;
        }
        .otp-box {
            background: #f0f4ff;
            border: 2px dashed #1a56db;
            border-radius: 10px;
            padding: 20px 32px;
            text-align: center;
            margin: 24px 0;
        }
        .otp-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }
        .otp-code {
            font-size: 38px;
            font-weight: bold;
            letter-spacing: 10px;
            color: #1a56db;
            font-family: 'Courier New', monospace;
        }
        .otp-expiry {
            font-size: 12px;
            color: #e53e3e;
            margin-top: 10px;
        }
        .warning {
            background: #fff8e1;
            border-right: 4px solid #f59e0b;
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 13px;
            color: #92400e;
            margin-top: 20px;
        }
        .footer {
            background: #f8f9fa;
            border-top: 1px solid #eee;
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #888;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $appName }}</h1>
            <p>{{ $purposeLabel }}</p>
        </div>

        <div class="body">
            <p>کاربر گرامی،</p>
            <p>
                کد تأیید شما برای <strong>{{ $purposeLabel }}</strong> به شرح زیر می‌باشد.
                این کد تنها یک بار قابل استفاده است.
            </p>

            <div class="otp-box">
                <div class="otp-label">کد تأیید شما</div>
                <div class="otp-code">{{ $code }}</div>
                <div class="otp-expiry">⏱ اعتبار این کد {{ $expiryMinutes }} دقیقه است</div>
            </div>

            <div class="warning">
                ⚠️ این کد محرمانه است. آن را با هیچکس به اشتراک نگذارید.
                کارمندان {{ $appName }} هرگز این کد را از شما نمی‌خواهند.
            </div>
        </div>

        <div class="footer">
            <p>اگر این درخواست را شما ارسال نکرده‌اید، این ایمیل را نادیده بگیرید.</p>
            <p style="margin-top:8px;">© {{ date('Y') }} {{ $appName }} — ارسال خودکار، پاسخ ندهید</p>
        </div>
    </div>
</body>
</html>
