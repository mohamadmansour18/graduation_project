<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Account Created - Nerd</title>
</head>
@php
    $logoPath = public_path('Logo/Nerd-Logo.png');
    $logoSrc = isset($message) && file_exists($logoPath)
        ? $message->embed($logoPath)
        : asset('Logo/Nerd-Logo.png');
@endphp
<body style="margin: 0; padding: 0; background-color: #f4f7fb; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f4f7fb; margin: 0; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width: 640px;">
                    <tr>
                        <td style="padding-bottom: 20px; text-align: center;">
                            <span dir="ltr" style="display: inline-block; font-size: 13px; color: #6b7280; letter-spacing: 0.08em; unicode-bidi: embed;">
                                Dashboard Access
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%); border-radius: 24px 24px 0 0; padding: 32px 24px; text-align: center;">
                            <img
                                src="{{ $logoSrc }}"
                                alt="Nerd Logo"
                                style="display: block; margin: 0 auto 18px; width: 88px; max-width: 88px; height: auto;"
                            >
                            <h1 style="margin: 0; font-size: 30px; line-height: 1.3; color: #ffffff; font-weight: 700;">
                                مرحباً {{ $supervisor->name }}
                            </h1>
                            <p style="margin: 12px 0 0; font-size: 16px; line-height: 1.8; color: #dbeafe;">
                                تم إنشاء حساب مشرف خاص بك في لوحة تحكم
                                <strong dir="ltr" style="unicode-bidi: embed;">Nerd</strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #ffffff; border-radius: 0 0 24px 24px; padding: 32px 24px 28px; box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);">
                            <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.9; color: #374151; text-align: center;">
                                يمكنك الآن استخدام حسابك للوصول إلى لوحة التحكم ومتابعة المهام الإدارية الممنوحة لك.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 0 auto 24px; border: 1px solid #dbeafe; border-radius: 18px; overflow: hidden;">
                                <tr>
                                    <td style="background-color: #eff6ff; padding: 18px 20px; text-align: center;">
                                        <p style="margin: 0 0 8px; font-size: 13px; color: #1d4ed8;">
                                            البريد الإلكتروني الخاص بالحساب
                                        </p>
                                        <p dir="ltr" style="margin: 0; font-size: 20px; line-height: 1.5; color: #0f172a; font-weight: 700; unicode-bidi: embed; word-break: break-word;">
                                            {{ $supervisor->email }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #fff7ed; border: 1px solid #fed7aa; border-radius: 16px; padding: 16px 18px; text-align: center;">
                                        <p style="margin: 0; font-size: 15px; line-height: 1.8; color: #9a3412;">
                                            استخدم كلمة المرور التي تم تزويدك بها من مسؤول النظام، ولا تشارك بيانات الدخول مع أي شخص آخر
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.9; color: #4b5563; text-align: center;">
                                تم إنشاء هذا الحساب بواسطة {{ $owner->name }}. إذا لم تكن تتوقع هذه الرسالة، يرجى التواصل مع إدارة النظام.
                            </p>

                            <p style="margin: 0; font-size: 15px; line-height: 1.9; color: #6b7280; text-align: center;">
                                مع خالص التحية<br>
                                <strong style="color: #111827;">فريق <span dir="ltr" style="unicode-bidi: embed;">Nerd</span></strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 18px 12px 0; text-align: center;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.8; color: #94a3b8;">
                                هذه رسالة تلقائية من تطبيق <span dir="ltr" style="unicode-bidi: embed;">Nerd</span> لإشعارك بإنشاء حساب مشرف في لوحة التحكم.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
