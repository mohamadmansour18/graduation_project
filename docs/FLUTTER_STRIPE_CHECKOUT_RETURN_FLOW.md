# دليل Flutter: العودة من Stripe Checkout إلى تطبيق Nerd

## الهدف

بعد إنهاء المستخدم الدفع في Stripe Checkout، لا تعتمد الواجهة على رابط النجاح أو الإلغاء كدليل على النتيجة. الرابط يعيد المستخدم إلى التطبيق فقط؛ المصدر الموثوق للحالة هو API في Backend الذي تحدّثه Stripe Webhook.

## الـAPI المتاح

### إنشاء جلسة Stripe Checkout

```http
POST /api/v1/user-mobile/test/payments/stripe/{testId}
Authorization: Bearer <access_token>
```

أهم حقول الاستجابة:

```json
{
  "data": {
    "payment_attempt_id": 123,
    "checkout_url": "https://checkout.stripe.com/...",
    "expires_at": 1780000000
  }
}
```

احتفظ بـ `payment_attempt_id` محليًا طوال رحلة الدفع. لا تستنتج نجاح الدفع من فتح `checkout_url` أو من الرجوع من Stripe.

### التحقق من حالة الدفع

```http
GET /api/v1/user-mobile/payments/attempts/{paymentAttemptId}/status
Authorization: Bearer <access_token>
```

مثال استجابة:

```json
{
  "success": true,
  "data": {
    "payment_attempt_id": 123,
    "purchase_id": 44,
    "test_id": 91,
    "status": "paid",
    "is_final": true,
    "test_access_granted": true,
    "expires_at": "2026-08-13T12:00:00+03:00",
    "paid_at": "2026-08-13T11:35:16+03:00"
  }
}
```

قيم `status` المتاحة:

| القيمة | سلوك Flutter |
| --- | --- |
| `pending` | اعرض حالة «جارٍ التحقق»، وأعد الطلب دوريًا. |
| `paid` | افتح الاختبار باستخدام `test_id`. لا تعرض النجاح قبل `test_access_granted = true`. |
| `failed` | اعرض فشل الدفع وزر إعادة المحاولة. |
| `cancelled` | أعد المستخدم إلى شاشة الاختبار أو الدفع. |
| `expired` | اعرض أن الجلسة انتهت وزر إنشاء عملية دفع جديدة. |

الـAPI يعيد `404` إذا كانت المحاولة غير موجودة أو لا تخص المستخدم صاحب access token. لا تحاول الوصول إلى محاولة مستخدم آخر.

## تدفق Flutter المقترح

1. استدعِ API إنشاء الجلسة واحفظ `payment_attempt_id`.
2. افتح `checkout_url` في متصفح خارجي أو Custom Tab / SFSafariViewController؛ لا تفتحها في WebView غير موثوق.
3. استقبل deep link أو Universal Link عند العودة.
4. استخرج `payment_attempt_id` من الرابط، واستخدم القيمة المحفوظة محليًا كخيار احتياطي إن لم تصل القيمة في الرابط.
5. انتقل إلى شاشة تحقق تمنع الضغط المتكرر على زر الدفع.
6. استدعِ API حالة المحاولة مباشرة، ثم كرره كل ثانيتين لمدة 30 ثانية كحد أقصى عند `pending`.
7. عند `paid` و`test_access_granted = true`، حدّث بيانات الاختبارات المشترَاة ثم افتح الاختبار.
8. عند انتهاء المهلة وحالة `pending`، أخبر المستخدم أن الدفع قيد التحقق مع زر «إعادة المحاولة» يعيد استدعاء API الحالة فقط، ولا ينشئ عملية Stripe جديدة تلقائيًا.

مثال pseudo-code:

```dart
final attemptId = uri.queryParameters['payment_attempt_id'] ?? storedAttemptId;

for (var retry = 0; retry < 15; retry++) {
  final result = await api.getPaymentAttemptStatus(attemptId);

  if (result.status == 'paid' && result.testAccessGranted) {
    await purchasesStore.refresh();
    context.go('/tests/${result.testId}');
    return;
  }

  if (result.status != 'pending') {
    showPaymentResult(result.status);
    return;
  }

  await Future.delayed(const Duration(seconds: 2));
}
```

## الروابط التي قد تصل للتطبيق

تستخدم Stripe روابط HTTPS التالية في الإنتاج:

```text
https://YOUR_DOMAIN/payment/return/success?session_id=...&payment_attempt_id=123
https://YOUR_DOMAIN/payment/return/cancel?payment_attempt_id=123
```

صفحات Laravel تحاول فتح custom deep link احتياطيًا:

```text
nerd://payment/return/success?payment_attempt_id=123
nerd://payment/return/cancel?payment_attempt_id=123
```

يجب أن يدعم Flutter المسارين أعلاه. لا تعتمد على `session_id` لاتخاذ قرار نجاح الدفع؛ هو للتتبع فقط.

## Android App Links

يُنصح باستخدام Android App Links على نفس `YOUR_DOMAIN` حتى يفتح رابط HTTPS التطبيق مباشرة.

1. أضف intent filter في `android/app/src/main/AndroidManifest.xml` لمساري `/payment/return/success` و`/payment/return/cancel`، مع `android:autoVerify="true"`.
2. انشر الملف التالي من نفس الدومين عبر HTTPS:

```text
https://YOUR_DOMAIN/.well-known/assetlinks.json
```

3. يجب أن يحتوي الملف على `package_name` الصحيح وSHA-256 fingerprint لشهادة توقيع release.
4. اختبره بالأمر:

```bash
adb shell pm get-app-links YOUR_ANDROID_PACKAGE
```

## iOS Universal Links

1. فعّل `Associated Domains` في Xcode.
2. أضف:

```text
applinks:YOUR_DOMAIN
```

3. انشر الملف من الدومين نفسه عبر HTTPS ومن دون redirect:

```text
https://YOUR_DOMAIN/.well-known/apple-app-site-association
```

4. أضف مساري `/payment/return/success*` و`/payment/return/cancel*` إلى `components` في الملف.
5. اختبر على جهاز فعلي بعد حذف التطبيق وإعادة تثبيته؛ iOS يحفظ إعداد Universal Link.

## استقبال الرابط في Flutter

استخدم حزمة موثوقة مثل `app_links` لاستقبال الرابط عند فتح التطبيق وهو مغلق أو يعمل في الخلفية. مرّر الرابط إلى طبقة واحدة مسؤولة عن routing لتجنب تنفيذ التحقق مرتين.

يجب أن تتعامل الطبقة مع:

- `getInitialLink()` عند فتح التطبيق لأول مرة.
- stream الروابط عندما يكون التطبيق مفتوحًا.
- منع تكرار المعالجة لنفس `payment_attempt_id`.
- التحقق من `host` و`path` عند Universal Link، ومن `scheme` و`host` عند custom scheme.

## إعداد الإنتاج المطلوب من Backend/DevOps

لا تعدّل Flutter هذه القيم. اضبطها في بيئة الإنتاج ثم نفذ cache config المناسب لـLaravel:

```env
APP_URL=https://YOUR_DOMAIN
PAYMENT_SUCCESS_URL=https://YOUR_DOMAIN/payment/return/success?session_id={CHECKOUT_SESSION_ID}
PAYMENT_CANCEL_URL=https://YOUR_DOMAIN/payment/return/cancel
PAYMENT_APP_DEEP_LINK_SCHEME=nerd
```

يجب أن يكون `YOUR_DOMAIN` متاحًا علنًا عبر HTTPS؛ Stripe لا يمكنه إعادة المستخدم إلى `localhost` أو عنوان شبكة داخلية في الإنتاج.

## قواعد أمان مهمة

- لا تفعّل الوصول للاختبار داخل Flutter من `success_url` أو deep link فقط.
- لا ترسل Stripe secret أو webhook secret إلى التطبيق.
- لا تستخدم `session_id` للوصول إلى بيانات الدفع من Flutter.
- لا توقف أو تستبدل Stripe webhook؛ هي الجهة الأساسية التي تؤكد الدفع حتى إن أغلق المستخدم المتصفح قبل العودة.
- زر «إعادة المحاولة» عند `failed` أو `expired` يجب أن ينشئ Checkout session جديدة فقط بعد تفاعل المستخدم الصريح.
