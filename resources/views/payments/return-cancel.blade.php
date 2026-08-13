<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#10234b">
    <title>تم إلغاء الدفع | Nerd</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; color: #17233c; font-family: Tahoma, Arial, sans-serif; background: radial-gradient(circle at 85% 5%, #dbe7ff 0, transparent 31%), radial-gradient(circle at 9% 94%, #fff1e7 0, transparent 28%), #f6f8fc; }
        .card { width: min(100%, 480px); overflow: hidden; border: 1px solid rgba(41, 82, 166, .14); border-radius: 30px; background: rgba(255, 255, 255, .93); box-shadow: 0 28px 70px rgba(18, 44, 96, .15); text-align: center; }
        .top { height: 8px; background: linear-gradient(90deg, #3c6df0, #f09851); }
        .content { padding: 38px 28px 30px; }
        .logo-wrap { width: 88px; height: 88px; display: grid; place-items: center; margin: 0 auto 22px; border-radius: 26px; background: #eef3ff; box-shadow: inset 0 0 0 1px #dbe6ff; }
        .logo { width: 64px; height: 64px; object-fit: contain; }
        .icon { width: 64px; height: 64px; display: grid; place-items: center; margin: 0 auto 20px; border-radius: 50%; color: #bc5b15; background: #fff0e5; font-size: 30px; font-weight: bold; }
        h1 { margin: 0 0 12px; font-size: 25px; line-height: 1.45; }
        p { margin: 0 auto; max-width: 360px; color: #64718a; font-size: 15px; line-height: 1.9; }
        .open { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; margin-top: 25px; padding: 12px 21px; border-radius: 14px; color: #fff; background: #3868e8; font-weight: bold; text-decoration: none; box-shadow: 0 10px 22px rgba(56, 104, 232, .25); }
        .note { margin-top: 18px; color: #8993a6; font-size: 12px; line-height: 1.7; }
    </style>
</head>
<body>
<main class="card">
    <div class="top"></div>
    <div class="content">
        <div class="logo-wrap"><img class="logo" src="{{ asset('images/landing/nerd-logo.png') }}" alt="Nerd"></div>
        <div class="icon" aria-hidden="true">×</div>
        <h1>تم إلغاء عملية الدفع</h1>
        <p>لم يتم خصم أي مبلغ من خلال هذه الصفحة. يمكنك العودة إلى التطبيق والمحاولة مجددًا متى أردت.</p>
        <a class="open" href="{{ $deepLink }}">العودة إلى تطبيق Nerd</a>
        <div class="note">إذا تم الدفع بالفعل، سيؤكد التطبيق العملية من الخادم قبل عرض النتيجة.</div>
    </div>
</main>
<script>window.setTimeout(() => { window.location.href = @json($deepLink); }, 450);</script>
</body>
</html>
