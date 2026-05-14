<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تطبيق Nerd غير مثبت</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Tahoma, Arial, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(85, 130, 255, 0.25), transparent 35%),
                linear-gradient(135deg, #eef2ff 0%, #ffffff 45%, #f8fafc 100%);
            color: #263238;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 460px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(85, 130, 255, 0.18);
            border-radius: 28px;
            padding: 34px 26px;
            text-align: center;
            box-shadow: 0 24px 70px rgba(38, 50, 56, 0.12);
        }

        .logo-wrap {
            width: 112px;
            height: 112px;
            margin: 0 auto 20px;
            border-radius: 32px;
            background: #eef2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 0 0 1px rgba(85, 130, 255, 0.16);
        }

        .logo {
            width: 82px;
            height: 82px;
            object-fit: contain;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 25px;
            color: #263238;
        }

        p {
            margin: 0;
            color: #60707a;
            font-size: 15px;
            line-height: 1.9;
        }

        .badge {
            display: inline-block;
            margin-top: 22px;
            padding: 10px 16px;
            border-radius: 999px;
            background: #eef2ff;
            color: #5582ff;
            font-size: 14px;
            font-weight: bold;
        }

        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #8d8d8d;
        }
    </style>
</head>

<body>
<main class="card">
    <div class="logo-wrap">
        <img
            class="logo"
            src="{{ asset('Logo/Nerd-Logo.png') }}"
            alt="Nerd Logo"
        >
    </div>

    <h1>تطبيق Nerd غير مثبت</h1>

    <p>
        يبدو أن الرابط الذي فتحته مخصص لتطبيق Nerd، لكن التطبيق غير مثبت على هذا الجهاز حالياً.
        عند نشر التطبيق لاحقاً سيتم تحويلك مباشرة إلى صفحة التحميل.
    </p>

    <div class="badge">
        رابط مشاركة اختبار من Nerd
    </div>

    <div class="footer">
        Nerd • منصة الاختبارات والخطط الدراسية
    </div>
</main>
</body>
</html>
