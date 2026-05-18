<style>
  :root {
    --bg: #0B1020;
    --panel: #121A2F;
    --panel-2: #17213A;
    --panel-3: #0F172A;
    --primary: #7AA2FF;
    --primary-strong: #5582FF;
    --accent: #22D3EE;
    --ink: #EAF0FF;
    --muted: #A8B3CF;
    --muted-2: #7F8AA8;
    --border: #283653;
    --ok: #34D399;
    --warn: #FBBF24;
    --bad: #FB7185;
    --purple: #C084FC;
    --code-bg: #060A14;
  }

  * { box-sizing: border-box; }

  body {
    direction: rtl;
    text-align: right;
    font-family: "Tahoma", "Arial", sans-serif;
    background: var(--bg);
    color: var(--ink);
    line-height: 1.9;
    margin: 0;
    padding: 22px;
  }

  h1, h2, h3, h4 {
    color: var(--primary);
    line-height: 1.5;
  }

  h1 {
    text-align: center;
    padding: 28px 22px;
    border-radius: 24px;
    background:
      radial-gradient(circle at top left, rgba(34, 211, 238, 0.20), transparent 34%),
      linear-gradient(135deg, #111A33, #0B1020 72%);
    border: 1px solid var(--border);
    color: var(--ink);
    box-shadow: 0 18px 48px rgba(0, 0, 0, 0.35);
  }

  h2 {
    margin-top: 38px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
  }

  h3 {
    margin-top: 28px;
    color: var(--accent);
  }

  h4 { color: var(--purple); }

  p, li { color: var(--ink); }

  a { color: var(--accent); }

  .subtitle {
    color: var(--muted);
    text-align: center;
    font-size: 16px;
    margin-top: -8px;
  }

  .box, .card {
    background: linear-gradient(180deg, var(--panel), var(--panel-3));
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 18px 22px;
    margin: 18px 0;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
  }

  .note {
    border-right: 5px solid var(--primary-strong);
    background: rgba(85, 130, 255, 0.12);
    padding: 14px 18px;
    border-radius: 14px;
    margin: 16px 0;
  }

  .success {
    border-right-color: var(--ok);
    background: rgba(52, 211, 153, 0.10);
  }

  .warning {
    border-right-color: var(--warn);
    background: rgba(251, 191, 36, 0.10);
  }

  .danger {
    border-right-color: var(--bad);
    background: rgba(251, 113, 133, 0.10);
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }

  .pill {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    background: rgba(122, 162, 255, 0.14);
    color: var(--primary);
    border: 1px solid rgba(122, 162, 255, 0.25);
    margin: 2px;
    font-size: 13px;
  }

  code {
    direction: ltr;
    unicode-bidi: embed;
    background: rgba(255, 255, 255, 0.06);
    color: #D9E7FF;
    padding: 2px 7px;
    border-radius: 7px;
    border: 1px solid rgba(255, 255, 255, 0.08);
  }

  pre {
    direction: ltr;
    text-align: left;
    background: var(--code-bg);
    color: #E5E7EB;
    padding: 18px;
    border-radius: 16px;
    overflow: auto;
    line-height: 1.65;
    border: 1px solid var(--border);
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin: 18px 0;
    font-size: 14px;
    background: var(--panel-3);
    border-radius: 14px;
    overflow: hidden;
  }

  th {
    background: #1E2C4E;
    color: var(--ink);
    font-weight: bold;
  }

  th, td {
    border: 1px solid var(--border);
    padding: 11px 12px;
    vertical-align: top;
  }

  tr:nth-child(even) td { background: rgba(255,255,255,0.025); }

  .ltr {
    direction: ltr;
    text-align: left;
  }

  .small { color: var(--muted); font-size: 13px; }

  .toc ol {
    margin: 0;
    padding-right: 20px;
  }

  .toc li {
    margin: 6px 0;
    color: var(--muted);
  }
</style>

# دليل نظام الدفع الإلكتروني في مشروع Nerd

<p class="subtitle">تصميم الدفع باستخدام Stripe Checkout + Webhook + جدول محاولات الدفع <code>payment_attempts</code></p>

<div class="note success">
<strong>الهدف الأساسي:</strong> المستخدم يشتري اختبارًا عامًا مدفوعًا مرة واحدة، وبعد نجاح الدفع يصبح لديه حق وصول دائم للاختبار. لا يتم فتح الاختبار لأن الفرونت قال إن الدفع نجح، بل فقط بعد أن يصل Webhook موثوق من Stripe ويحدث قاعدة البيانات.
</div>

---

## الفهرس

<div class="toc">

1. [الفكرة العامة](#الفكرة-العامة)
2. [المصطلحات الأساسية](#المصطلحات-الأساسية)
3. [الجداول المعتمدة في نظام الدفع](#الجداول-المعتمدة-في-نظام-الدفع)
4. [لماذا أضفنا جدول payment_attempts؟](#لماذا-أضفنا-جدول-payment_attempts)
5. [القيم والحالات المعتمدة](#القيم-والحالات-المعتمدة)
6. [Workflow إنشاء جلسة الدفع](#workflow-إنشاء-جلسة-الدفع)
7. [Workflow نجاح الدفع](#workflow-نجاح-الدفع)
8. [Workflow فشل البطاقة](#workflow-فشل-البطاقة)
9. [Workflow انتهاء جلسة الدفع](#workflow-انتهاء-جلسة-الدفع)
10. [Workflow إعادة استخدام جلسة صالحة](#workflow-إعادة-استخدام-جلسة-صالحة)
11. [حالات الاستخدام الكاملة وتأثيرها على الجداول](#حالات-الاستخدام-الكاملة-وتأثيرها-على-الجداول)
12. [دور الفرونت](#دور-الفرونت)
13. [دور Webhook](#دور-webhook)
14. [دور Command التنظيف](#دور-command-التنظيف)
15. [قواعد الأمان المهمة](#قواعد-الأمان-المهمة)
16. [ملخص سريع](#ملخص-سريع)

</div>

---

## الفكرة العامة

نظام الدفع لدينا مبني على Stripe Hosted Checkout Page.

أي أن التطبيق لا يأخذ بيانات البطاقة ولا يخزنها. بدل ذلك:

```text
Flutter App
  -> يطلب من Laravel إنشاء جلسة دفع
Laravel
  -> يتحقق من قواعد الشراء
  -> ينشئ أو يعيد استخدام Checkout Session
  -> يرجع checkout_url
Flutter
  -> يفتح checkout_url للمستخدم
Stripe
  -> يعالج الدفع
  -> يعيد المستخدم إلى success_url أو cancel_url
  -> يرسل Webhook إلى Laravel
Laravel Webhook
  -> يثبت الدفع في قاعدة البيانات
```

<div class="note warning">
<strong>قاعدة ذهبية:</strong> روابط <code>success_url</code> و <code>cancel_url</code> خاصة بتجربة المستخدم فقط. أما تثبيت الدفع الحقيقي فيتم من خلال <code>Stripe Webhook</code> فقط.
</div>

---

## المصطلحات الأساسية

| المصطلح | الشرح المبسط |
|---|---|
| Provider | مزود الدفع، مثل <code>stripe</code> أو لاحقًا <code>google_play</code>. |
| Checkout Session | جلسة دفع ينشئها Stripe، وتحتوي السعر والعملة وروابط الرجوع. |
| checkout_url | الرابط الذي يفتحه الفرونت للمستخدم حتى يدفع داخل صفحة Stripe. |
| Webhook | API عندنا يستدعيه Stripe تلقائيًا ليخبرنا أن الدفع نجح أو فشل أو انتهت الجلسة. |
| PaymentIntent | كائن داخل Stripe يمثل محاولة الدفع المالية. |
| Charge | العملية المالية الفعلية على البطاقة. |
| Metadata | بيانات نرسلها إلى Stripe مثل <code>payment_attempt_id</code> حتى تعود إلينا في Webhook. |
| Minor Unit | طريقة Stripe في استقبال المبلغ بالسنتات. مثال: <code>10.00 USD</code> ترسل إلى Stripe كـ <code>1000</code>. |

---

## الجداول المعتمدة في نظام الدفع

### 1. جدول `test_purchases`

هذا الجدول يمثل الشراء النهائي أو حق الوصول.

بمعنى:

```text
هل المستخدم اشترى هذا الاختبار فعلًا؟
```

أهم الحقول:

| الحقل | الوظيفة |
|---|---|
| test_id | الاختبار الذي يريد المستخدم شراءه. |
| buyer_user_id | المستخدم المشتري. |
| seller_user_id | صاحب الاختبار. |
| gross_amount | السعر الكامل. |
| platform_fee_amount | ربح المنصة. |
| seller_net_amount | صافي ربح صاحب الاختبار. |
| currency | العملة، حاليًا <code>usd</code>. |
| payment_provider | مزود الدفع، حاليًا <code>stripe</code>. |
| payment_reference | مرجع الدفع النهائي، غالبًا <code>cs_...</code>. |
| payment_status | حالة الشراء النهائي. |
| purchased_at | وقت نجاح الشراء. |

---

### 2. جدول `payment_attempts`

هذا الجدول يمثل محاولات الدفع.

أي أن المستخدم قد يحاول الدفع أكثر من مرة، لكن يبقى لديه سجل واحد في `test_purchases`.

أهم الحقول:

| الحقل | الوظيفة |
|---|---|
| test_purchase_id | يربط المحاولة بالشراء النهائي. |
| payment_provider | مزود الدفع، مثل <code>stripe</code>. |
| provider_reference | مرجع Checkout Session مثل <code>cs_test_...</code>. |
| provider_payment_intent_reference | مرجع PaymentIntent مثل <code>pi_...</code>. |
| checkout_url | رابط صفحة الدفع. |
| amount | المبلغ بالدولار بصورته الطبيعية مثل <code>10.00</code>. |
| currency | العملة. |
| status | حالة محاولة الدفع. |
| failure_code | كود الفشل إن وجد. |
| failure_message | رسالة الفشل إن وجدت. |
| expires_at | وقت انتهاء الجلسة. |
| paid_at | وقت نجاح المحاولة. |
| failed_at | وقت تسجيل فشل البطاقة. |
| expired_at | وقت انتهاء الجلسة. |
| cancelled_at | وقت الإلغاء إن استخدمناه لاحقًا. |
| metadata | بيانات إضافية. |

---

## لماذا أضفنا جدول payment_attempts؟

قبل إضافة هذا الجدول، كان `test_purchases` يحمل كل شيء:

```text
test_purchases = حق وصول + محاولة دفع + مرجع Stripe
```

وهذا سبب مشكلة في الحالات التالية:

```text
المستخدم فتح جلسة A
ثم فتح جلسة B
ثم دفع جلسة A القديمة
```

لو كنا نخزن مرجعًا واحدًا فقط في `test_purchases.payment_reference`، فقد يضيع أثر الجلسة القديمة.

بعد إضافة `payment_attempts` صار التصميم:

```text
test_purchases = حق الوصول النهائي
payment_attempts = كل محاولات الدفع
```

<div class="note success">
<strong>الفائدة:</strong> نستطيع تتبع كل جلسة دفع، سواء نجحت أو فشلت أو انتهت، مع إبقاء حق الوصول النهائي في جدول واحد واضح.
</div>

---

## القيم والحالات المعتمدة

### حالات `test_purchases.payment_status`

| الحالة | معناها | هل تفتح الاختبار؟ |
|---|---|---|
| معلقة | يوجد شراء قيد المعالجة أو محاولة دفع مفتوحة. | لا |
| مدفوعة | الدفع نجح وتم تثبيت الشراء. | نعم |
| فاشلة | فشل تجهيز العملية أو خطأ واضح. | لا |
| ملغاة | لا توجد محاولة دفع صالحة بعد انتهاء أو إلغاء المحاولات. | لا |
| مردودة | تم رد المبلغ لاحقًا. | حسب قرار النظام، غالبًا لا |

---

### حالات `payment_attempts.status`

| الحالة | معناها |
|---|---|
| معلقة | الجلسة موجودة ولم نعرف نتيجتها النهائية بعد. |
| ناجحة | هذه المحاولة نجحت. |
| فاشلة | تم تسجيل فشل في الدفع، مثل بطاقة مرفوضة. |
| منتهية | الجلسة انتهت صلاحيتها. |
| ملغاة | المحاولة أُلغيت صراحةً، إن أضفنا زر إلغاء لاحقًا. |

<div class="note warning">
فشل البطاقة لا يعني دائمًا إلغاء الجلسة؛ فقد يستطيع المستخدم إدخال بطاقة أخرى داخل نفس صفحة Stripe. لذلك قد نسجل بيانات الفشل مع إبقاء المحاولة معلقة إلى أن تنجح أو تنتهي.
</div>

---

## Workflow إنشاء جلسة الدفع

عندما يضغط المستخدم زر الشراء:

```text
1. Flutter يستدعي API إنشاء الدفع.
2. Laravel يتحقق من المستخدم عبر auth token.
3. Laravel يجلب بيانات الاختبار.
4. Laravel يتحقق من قواعد الشراء:
   - الاختبار موجود.
   - الاختبار عام.
   - الاختبار معتمد.
   - الاختبار مدفوع.
   - المستخدم ليس صاحب الاختبار.
   - المستخدم لم يشتره سابقًا.
5. Laravel يحسب:
   - gross_amount
   - platform_fee_amount
   - seller_net_amount
6. Laravel يجهز test_purchase.
7. Laravel يبحث عن payment_attempt صالحة لم تنتهِ.
8. إذا وجدها، يعيد checkout_url القديم.
9. إذا لم يجدها، ينشئ payment_attempt جديدة.
10. Laravel يطلب من Stripe إنشاء Checkout Session.
11. Laravel يخزن:
    - provider_reference = cs_...
    - checkout_url
    - expires_at
12. Laravel يرجع checkout_url للفرونت.
```

---

## Workflow نجاح الدفع

عندما يدفع المستخدم بنجاح:

```text
1. Stripe يرسل checkout.session.completed إلى Laravel Webhook.
2. Laravel يتحقق من توقيع Stripe.
3. Laravel يقرأ payment_attempt_id من metadata.
4. Laravel يجد payment_attempt.
5. Laravel يحول payment_attempts.status إلى ناجحة.
6. Laravel يحول test_purchases.payment_status إلى مدفوعة.
7. Laravel يضع purchased_at.
8. Laravel يسجل audit log.
9. Laravel يترك مكانًا لإطلاق Event إشعارات للمشتري والبائع.
10. API تفاصيل الاختبار لاحقًا يرجع has_purchased = true.
```

---

## Workflow فشل البطاقة

عندما يدخل المستخدم بطاقة فاشلة مثل بطاقة Stripe التجريبية للفشل:

```text
1. Stripe يرسل payment_intent.payment_failed.
2. Laravel يجد payment_attempt من metadata أو PaymentIntent ID.
3. Laravel يسجل failure_code و failure_message.
4. لا يتم فتح الاختبار.
5. لا يتم تحويل test_purchase إلى مدفوعة.
6. لا نلغي الشراء النهائي مباشرة.
```

السبب:

```text
فشل بطاقة واحدة لا يعني أن جلسة Checkout انتهت.
قد يجرّب المستخدم بطاقة أخرى وتنجح.
```

---

## Workflow انتهاء جلسة الدفع

عندما يفتح المستخدم صفحة الدفع ولا يدفع حتى تنتهي صلاحية الجلسة:

```text
1. Stripe يرسل checkout.session.expired.
2. Laravel يجد payment_attempt.
3. Laravel يحول payment_attempts.status إلى منتهية.
4. Laravel يفحص هل توجد محاولة معلقة أخرى لنفس test_purchase.
5. إذا لا توجد محاولة صالحة:
   test_purchases.payment_status = ملغاة
6. إذا توجد محاولة أخرى صالحة:
   لا يغير test_purchases.
```

---

## Workflow إعادة استخدام جلسة صالحة

إذا فتح المستخدم جلسة دفع ثم خرج، وبعد وقت قصير حاول الشراء مرة ثانية:

```text
1. Laravel يجهز test_purchase.
2. Laravel يبحث عن payment_attempt:
   - status = معلقة
   - checkout_url ليس فارغًا
   - provider_reference ليس فارغًا
   - expires_at أكبر من الوقت الحالي
3. إذا وجد محاولة صالحة:
   يرجع نفس checkout_url.
4. لا يتصل بـ Stripe من جديد.
```

<div class="note success">
هذا يقلل أخطاء الشبكة مثل <code>SSL connection timeout</code> أو <code>Could not resolve host: api.stripe.com</code> لأنه يقلل عدد الاتصالات الخارجية مع Stripe.
</div>

---

## حالات الاستخدام الكاملة وتأثيرها على الجداول

### جدول الحالات المختصر

| الحالة | ما الذي يحدث للمستخدم؟ | test_purchases | payment_attempts |
|---|---|---|---|
| المستخدم يضغط شراء أول مرة | يتم إنشاء جلسة جديدة | معلقة | معلقة + checkout_url + cs_... |
| المستخدم يفتح الجلسة ويغلق التطبيق دون إدخال بيانات | لا يحدث نجاح ولا فشل فوري | تبقى معلقة | تبقى معلقة حتى تنتهي |
| المستخدم يعود قبل انتهاء الجلسة | يرجع له نفس checkout_url | تبقى معلقة | نفس المحاولة تبقى معلقة |
| المستخدم يعود بعد انتهاء الجلسة | ينشئ جلسة جديدة | قد تكون ملغاة ثم تعود معلقة عند المحاولة الجديدة | القديمة منتهية، والجديدة معلقة |
| المستخدم يدفع بنجاح | يفتح الاختبار بعد webhook | مدفوعة + purchased_at | ناجحة + paid_at |
| المستخدم يدخل بطاقة مرفوضة | تظهر له رسالة فشل في Stripe | تبقى معلقة | نسجل failure_code و failure_message، وقد تبقى معلقة |
| المستخدم يدخل بطاقة فاشلة ثم بطاقة صحيحة | ينجح الدفع في النهاية | مدفوعة | المحاولة تصبح ناجحة في النهاية |
| المستخدم يضغط Cancel ويرجع للتطبيق | تجربة المستخدم تعود لصفحة cancel | غالبًا تبقى معلقة حتى انتهاء الجلسة أو تنظيفها | تبقى معلقة حتى expire أو command |
| المستخدم لا يعود أبدًا | لا يحدث شيء من الفرونت | تتحول إلى ملغاة عند expired أو command | تتحول إلى منتهية |
| Webhook لم يصل بسبب توقف السيرفر | لا يتم التحديث فورًا | قد تبقى معلقة | قد تبقى معلقة |
| Command التنظيف يعمل | ينظف القديم | يحول الشراء إلى ملغاة إذا لا توجد attempts صالحة | يحول المنتهي إلى منتهية |
| المستخدم يحاول شراء اختبار اشتراه سابقًا | يمنع الشراء | مدفوعة | لا ينشئ محاولة جديدة |
| المستخدم يحاول شراء اختباره الخاص | يمنع الشراء | لا تغيير | لا تغيير |
| المستخدم يحاول شراء اختبار مجاني | يمنع الشراء | لا تغيير | لا تغيير |
| المستخدم يحاول شراء اختبار غير معتمد | يمنع الشراء | لا تغيير | لا تغيير |
| المستخدم يضغط الزر مرتين بسرعة | idempotency/reuse يقلل التكرار | سجل واحد للشراء | محاولة واحدة أو إعادة استخدام محاولة صالحة |
| المستخدم يفتح جلستين ويدفع القديمة | Webhook يربطها بالمحاولة من metadata | مدفوعة | المحاولة القديمة تصبح ناجحة |
| Stripe يفشل بسبب شبكة أثناء إنشاء الجلسة | يرجع خطأ تجهيز الدفع | قد تبقى معلقة أو حسب السياسة | المحاولة تسجل فشل تجهيز إن أنشئت |

---

### الحالة 1: فتح جلسة ثم إغلاق التطبيق دون إدخال بيانات

```text
المستخدم ضغط شراء
فتح صفحة Stripe
أغلق التطبيق أو المتصفح
لم يدخل بطاقة
```

ما الذي يحدث؟

```text
لا يصل checkout.session.completed
لا يصل payment_intent.payment_failed
قد يصل checkout.session.expired بعد انتهاء الجلسة
```

تأثير الجداول:

| الجدول | التغيير |
|---|---|
| test_purchases | تبقى الحالة معلقة إلى أن تنتهي الجلسة أو يعمل command التنظيف. |
| payment_attempts | تبقى معلقة، ثم تتحول إلى منتهية عند expired أو command. |

---

### الحالة 2: فتح جلسة ثم العودة قبل انتهاء صلاحيتها

```text
المستخدم فتح جلسة دفع
خرج
عاد بعد 10 دقائق
ضغط شراء مرة ثانية
```

ما الذي يحدث؟

```text
Laravel يجد payment_attempt معلقة وصالحة
يرجع نفس checkout_url
لا يتصل بـ Stripe من جديد
```

تأثير الجداول:

| الجدول | التغيير |
|---|---|
| test_purchases | لا تغيير، تبقى معلقة. |
| payment_attempts | لا تنشأ محاولة جديدة، يتم استخدام نفس المحاولة. |

---

### الحالة 3: فتح جلسة ثم العودة بعد انتهاء صلاحيتها

```text
المستخدم فتح جلسة دفع
خرج
عاد بعد انتهاء expires_at
```

ما الذي يحدث؟

```text
المحاولة القديمة لم تعد صالحة
Laravel ينشئ payment_attempt جديدة
Stripe ينشئ Checkout Session جديدة
```

تأثير الجداول:

| الجدول | التغيير |
|---|---|
| test_purchases | يتحول إلى معلقة عند بدء محاولة جديدة. |
| payment_attempts | القديمة منتهية، والجديدة معلقة. |

---

### الحالة 4: فشل البطاقة

```text
المستخدم أدخل بطاقة مرفوضة
```

ما الذي يحدث؟

```text
Stripe يرسل payment_intent.payment_failed
Laravel يسجل سبب الفشل
لا يفتح الاختبار
```

تأثير الجداول:

| الجدول | التغيير |
|---|---|
| test_purchases | تبقى معلقة. |
| payment_attempts | يتم تسجيل failure_code و failure_message. ويمكن أن تبقى معلقة حتى انتهاء الجلسة. |

---

### الحالة 5: فشل البطاقة ثم نجاح بطاقة أخرى

```text
المستخدم أدخل بطاقة فاشلة
ثم أدخل بطاقة صحيحة داخل نفس صفحة Stripe
```

ما الذي يحدث؟

```text
أولًا يصل payment_intent.payment_failed
ثم لاحقًا checkout.session.completed
```

تأثير الجداول:

| الجدول | التغيير |
|---|---|
| test_purchases | في النهاية تصبح مدفوعة. |
| payment_attempts | في النهاية تصبح ناجحة، حتى لو كان مسجلًا فيها سبب فشل سابق. |

---

### الحالة 6: نجاح الدفع

```text
المستخدم دفع بنجاح
```

ما الذي يحدث؟

```text
Stripe يرسل checkout.session.completed
Laravel يثبت الدفع
```

تأثير الجداول:

| الجدول | التغيير |
|---|---|
| test_purchases | payment_status = مدفوعة، purchased_at = now. |
| payment_attempts | status = ناجحة، paid_at = now. |

---

### الحالة 7: انتهاء الجلسة دون دفع

```text
المستخدم لم يدفع حتى انتهت جلسة Stripe
```

ما الذي يحدث؟

```text
Stripe يرسل checkout.session.expired
أو Command التنظيف يلتقطها لاحقًا
```

تأثير الجداول:

| الجدول | التغيير |
|---|---|
| test_purchases | تتحول إلى ملغاة إذا لا توجد attempts معلقة صالحة أخرى. |
| payment_attempts | تتحول إلى منتهية. |

---

### الحالة 8: عدم وصول Webhook

```text
السيرفر توقف
أو Stripe CLI مغلق في التطوير
أو حدثت مشكلة شبكة
```

ما الذي يحدث؟

```text
قاعدة البيانات قد تبقى معلقة مؤقتًا
```

الحل:

```text
Command التنظيف يعمل دوريًا
ويحوّل المحاولات المنتهية إلى منتهية
ثم يلغي test_purchase إذا لا توجد attempt صالحة
```

---

## دور الفرونت

الفرونت مسؤول عن:

```text
1. إرسال طلب إنشاء جلسة الدفع.
2. إرسال Idempotency-Key مع الطلب.
3. قراءة checkout_url من response.
4. فتح checkout_url عبر url_launcher أو in-app browser.
5. عند العودة من Stripe، إعادة جلب تفاصيل الاختبار.
6. الاعتماد على has_purchased القادم من الباك.
```

الفرونت غير مسؤول عن:

```text
تثبيت الدفع
تغيير payment_status
التحقق من Stripe
إرسال Webhook
```

---

## دور Webhook

Webhook هو مصدر الحقيقة لتثبيت الدفع.

الأحداث التي نعتمدها:

| الحدث | ماذا نفعل؟ |
|---|---|
| checkout.session.completed | نثبت الدفع ونفتح الاختبار. |
| checkout.session.expired | ننهي محاولة الدفع ونلغي الشراء إذا لا توجد محاولات صالحة. |
| payment_intent.payment_failed | نسجل سبب الفشل ولا نلغي الشراء النهائي مباشرة. |

---

## دور Command التنظيف

الأمر المقترح:

```bash
php artisan payments:cancel-stale-attempts
```

وظيفته:

```text
1. يبحث عن payment_attempts معلقة انتهى وقتها.
2. يحولها إلى منتهية.
3. يفحص test_purchases المرتبطة بها.
4. إذا لا توجد محاولة معلقة صالحة، يحول test_purchase إلى ملغاة.
```

الجدولة المقترحة:

```php
$schedule->command('payments:cancel-stale-attempts')
    ->everyTenMinutes()
    ->withoutOverlapping();
```

<div class="note warning">
الـ Command ليس بديلًا عن Webhook، بل هو خط دفاع احتياطي حتى لا تبقى سجلات معلقة للأبد.
</div>

---

## قواعد الأمان المهمة

1. لا تثق بالفرونت في إثبات الدفع.
2. لا تجعل `success_url` يفتح الاختبار.
3. لا تقبل `buyer_user_id` من الطلب؛ خذه من التوكن.
4. تحقق من توقيع Stripe Webhook.
5. استخدم Metadata لربط Stripe Session بالـ `payment_attempt_id`.
6. استخدم Idempotency Key في API إنشاء الجلسة.
7. استخدم Idempotency Key عند إنشاء جلسة Stripe اعتمادًا على `attempt_id`.
8. لا تسجل بيانات حساسة مثل أسرار Stripe أو بيانات البطاقات.
9. اجعل الإشعارات وعمليات الإحصائيات الثقيلة عبر Events/Listeners و Queue.

---

## ملخص سريع

<div class="box">

<strong>النتيجة النهائية للتصميم:</strong>

```text
test_purchases
= هل المستخدم اشترى الاختبار؟

payment_attempts
= ماذا حدث في كل محاولة دفع؟
```

<strong>فتح الاختبار يحدث فقط عندما:</strong>

```text
test_purchases.payment_status = مدفوعة
```

<strong>وهذا لا يحدث إلا بعد:</strong>

```text
checkout.session.completed من Stripe Webhook
```

</div>

---

<p class="small">تم إعداد هذا الملف كمرجع تنفيذي لنظام الدفع الإلكتروني في مشروع Nerd، مع دعم إعادة استخدام الجلسات الصالحة، وتتبع محاولات الدفع، ومعالجة النجاح والفشل والانتهاء والتنظيف الدوري.</p>
