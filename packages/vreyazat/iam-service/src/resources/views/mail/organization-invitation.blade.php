<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} - دعوت‌نامه عضویت</title>
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
            background: linear-gradient(135deg, #059669, #047857);
            padding: 28px 32px;
            text-align: center;
        }
        .header h1 {
            color: #fff;
            font-size: 20px;
            font-weight: bold;
        }
        .header p {
            color: rgba(255,255,255,0.85);
            font-size: 13px;
            margin-top: 6px;
        }
        .body { padding: 32px; }
        .body p {
            font-size: 15px;
            line-height: 1.9;
            color: #444;
            margin-bottom: 16px;
        }
        .org-box {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 10px;
            padding: 18px 20px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .org-icon {
            font-size: 32px;
        }
        .org-name {
            font-size: 18px;
            font-weight: bold;
            color: #065f46;
        }
        .org-role {
            font-size: 13px;
            color: #6b7280;
            margin-top: 2px;
        }
        .btn-wrap {
            text-align: center;
            margin: 28px 0 20px;
        }
        .btn {
            display: inline-block;
            background: #059669;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .expiry-note {
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            margin-top: 8px;
        }
        .url-box {
            background: #f3f4f6;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 12px;
            color: #6b7280;
            word-break: break-all;
            margin-top: 12px;
            text-align: center;
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
            <p>دعوت‌نامه عضویت در سازمان</p>
        </div>

        <div class="body">
            <p>سلام،</p>
            <p>
                شما به عضویت در سازمان زیر دعوت شده‌اید.
                برای پذیرش دعوت‌نامه، روی دکمه زیر کلیک کنید.
            </p>

            <div class="org-box">
                <div class="org-icon">🏢</div>
                <div>
                    <div class="org-name">{{ $organization->name ?? 'سازمان' }}</div>
                    <div class="org-role">نقش: {{ $roleLabel }}</div>
                </div>
            </div>

            <div class="btn-wrap">
                <a href="{{ $inviteUrl }}" class="btn">پذیرش دعوت‌نامه</a>
                <div class="expiry-note">
                    ⏱ این دعوت‌نامه تا {{ \Carbon\Carbon::parse($invitation->expires_at)->locale('fa')->diffForHumans() }} معتبر است
                </div>
            </div>

            <p style="font-size:13px; color:#6b7280;">
                اگر دکمه بالا کار نمی‌کند، لینک زیر را در مرورگر خود وارد کنید:
            </p>
            <div class="url-box">{{ $inviteUrl }}</div>
        </div>

        <div class="footer">
            <p>اگر این دعوت‌نامه را شما درخواست نکرده‌اید، این ایمیل را نادیده بگیرید.</p>
            <p style="margin-top:8px;">© {{ date('Y') }} {{ $appName }} — ارسال خودکار، پاسخ ندهید</p>
        </div>
    </div>
</body>
</html>
