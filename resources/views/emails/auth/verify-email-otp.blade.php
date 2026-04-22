<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - Nerd</title>
</head>
@php
    $logoPath = public_path('Logo/Nerd-Logo.png');
    $logoSrc = isset($message) && file_exists($logoPath)
        ? $message->embed($logoPath)
        : asset('Logo/Nerd-Logo.png');

    $isPasswordReset = ($purpose ?? null) === \App\Enums\PurposeOTP::Password_Reset->value;

    $emailCopy = $isPasswordReset
        ? [
            'title' => 'Reset Password',
            'intro' => 'طلبت إعادة تعيين كلمة المرور لحسابك في نيرد',
            'instruction' => 'لإعادة تعيين كلمة المرور الخاصة بك، استخدم رمز التحقق التالي:',
            'fallback' => 'إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذه الرسالة بكل أمان',
            'footer' => 'هذه رسالة تلقائية من تطبيق <span dir="ltr" style="unicode-bidi: embed;">Nerd</span> لإعادة تعيين كلمة المرور.',
        ]
        : [
            'title' => 'Email Verification',
            'intro' => 'أهلاً بك في نيرد، يسعدنا انضمامك إلينا',
            'instruction' => 'لإكمال إنشاء حسابك وتأكيد بريدك الإلكتروني، استخدم رمز التحقق التالي:',
            'fallback' => 'إذا لم تقم بإنشاء هذا الحساب، يمكنك تجاهل هذه الرسالة بكل أمان',
            'footer' => 'هذه رسالة تلقائية من تطبيق <span dir="ltr" style="unicode-bidi: embed;">Nerd</span> لتأكيد البريد الإلكتروني.',
        ];
@endphp
<body style="margin: 0; padding: 0; background-color: #f4f7fb; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f4f7fb; margin: 0; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width: 640px;">
                    <tr>
                        <td style="padding-bottom: 20px; text-align: center;">
                            <span dir="ltr" style="display: inline-block; font-size: 13px; color: #6b7280; letter-spacing: 0.08em; unicode-bidi: embed;">
                                {{ $emailCopy['title'] }}
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
                                مرحباً {{ $user->name }}
                            </h1>
                            <p style="margin: 12px 0 0; font-size: 16px; line-height: 1.8; color: #dbeafe;">
                                {{ $emailCopy['intro'] }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #ffffff; border-radius: 0 0 24px 24px; padding: 32px 24px 28px; box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);">
                            <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.9; color: #374151; text-align: center;">
                                {{ $emailCopy['instruction'] }}
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 0 auto 24px;">
                                <tr>
                                    <td align="center">
                                        <div dir="ltr" style="display: inline-block; min-width: 220px; padding: 18px 28px; border-radius: 18px; background-color: #eff6ff; border: 1px solid #bfdbfe; font-size: 34px; font-weight: 700; letter-spacing: 10px; color: #1d4ed8; text-align: center; unicode-bidi: embed;">
                                            {{ $otpCode }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #fff7ed; border: 1px solid #fed7aa; border-radius: 16px; padding: 16px 18px; text-align: center;">
                                        <p style="margin: 0; font-size: 15px; line-height: 1.8; color: #9a3412;">
                                            هذا الرمز صالح لمدة <strong>5 دقائق فقط</strong>، لذا يُرجى استخدامه في أقرب وقت
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.9; color: #4b5563; text-align: center;">
                                {{ $emailCopy['fallback'] }}
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
                                {!! $emailCopy['footer'] !!}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
