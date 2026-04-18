<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Alert - Nerd</title>
</head>
@php
    $logoPath = public_path('Logo/Nerd-Logo-White.png');
    $logoSrc = isset($message) && file_exists($logoPath)
        ? $message->embed($logoPath)
        : asset('Logo/Nerd-Logo-White.png');
@endphp
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f8fafc; margin: 0; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width: 660px;">
                    <tr>
                        <td style="padding-bottom: 18px; text-align: center;">
                            <span dir="ltr" style="display: inline-block; font-size: 13px; color: #64748b; letter-spacing: 0.08em; unicode-bidi: embed;">
                                Security Alert
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td style="background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%); border-radius: 24px 24px 0 0; padding: 32px 24px; text-align: center;">
                            <img
                                src="{{ $logoSrc }}"
                                alt="Nerd Logo"
                                style="display: block; margin: 0 auto 18px; width: 88px; max-width: 88px; height: auto;"
                            >
                            <h1 style="margin: 0; font-size: 30px; line-height: 1.35; color: #ffffff; font-weight: 700;">
                                تنبيه أمني على حسابك
                            </h1>
                            <p style="margin: 12px 0 0; font-size: 16px; line-height: 1.9; color: #fee2e2;">
                                مرحباً {{ $user->name }}، رصدنا نشاطًا غير اعتيادي متعلقًا بمحاولات تسجيل الدخول إلى حسابك في
                                <strong dir="ltr" style="unicode-bidi: embed;">Nerd</strong>.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #ffffff; border-radius: 0 0 24px 24px; padding: 32px 24px 28px; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 18px; padding: 18px 20px; text-align: center;">
                                        <p style="margin: 0 0 6px; font-size: 14px; color: #991b1b;">
                                            تم رصد عدة محاولات فاشلة لتسجيل الدخول
                                        </p>
                                        <p style="margin: 0; font-size: 28px; line-height: 1.4; color: #b91c1c; font-weight: 700;">
                                            {{ $attemptsCount }} محاولة فاشلة
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.9; color: #334155; text-align: center;">
                                فيما يلي آخر المعلومات المتاحة عن آخر محاولة تم رصدها:
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 18px;">
                                <tr>
                                    <td style="padding: 18px 20px; border-bottom: 1px solid #e2e8f0;">
                                        <p style="margin: 0 0 8px; font-size: 13px; color: #64748b;">
                                            عنوان <span dir="ltr" style="unicode-bidi: embed;">IP</span>
                                        </p>
                                        <p dir="ltr" style="margin: 0; font-size: 16px; color: #0f172a; font-weight: 600; unicode-bidi: embed; text-align: left;">
                                            {{ $ipAddress ?? 'Not Available' }}
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="margin: 0 0 8px; font-size: 13px; color: #64748b;">
                                            نوع الجهاز / المتصفح
                                        </p>
                                        <p dir="ltr" style="margin: 0; font-size: 14px; line-height: 1.9; color: #334155; unicode-bidi: embed; text-align: left; word-break: break-word;">
                                            {{ $userAgent ?? 'Not Available' }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #fff7ed; border: 1px solid #fed7aa; border-radius: 16px; padding: 16px 18px;">
                                        <p style="margin: 0; font-size: 15px; line-height: 1.9; color: #9a3412; text-align: center;">
                                            إذا لم تكن هذه المحاولات صادرة منك، فننصحك بتغيير كلمة المرور فورًا والتأكد من أن بيانات الدخول الخاصة بك لم تتم مشاركتها مع أي جهة أخرى
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.9; color: #475569; text-align: center;">
                                إذا كنت أنت من حاول تسجيل الدخول، يمكنك تجاهل هذه الرسالة
                            </p>

                            <p style="margin: 0; font-size: 15px; line-height: 1.9; color: #64748b; text-align: center;">
                                مع خالص التحية<br>
                                <strong style="color: #111827;">فريق <span dir="ltr" style="unicode-bidi: embed;">Nerd</span></strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 18px 12px 0; text-align: center;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.8; color: #94a3b8;">
                                هذه رسالة أمنية تلقائية من تطبيق <span dir="ltr" style="unicode-bidi: embed;">Nerd</span> لحماية حسابك.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
