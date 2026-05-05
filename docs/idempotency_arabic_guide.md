# دليل احترافي لفهم Idempotency في Laravel APIs

<style>
  :root {
    --bg: #0f172a;
    --bg-soft: #111827;
    --card: #1e293b;
    --card-2: #0b1220;
    --text: #e5e7eb;
    --muted: #94a3b8;
    --border: #334155;
    --primary: #5582FF;
    --primary-2: #7da0ff;
    --primary-soft: rgba(85, 130, 255, 0.14);
    --success: #22c55e;
    --success-soft: rgba(34, 197, 94, 0.14);
    --warning: #f59e0b;
    --warning-soft: rgba(245, 158, 11, 0.16);
    --danger: #ef4444;
    --danger-soft: rgba(239, 68, 68, 0.14);
    --info: #38bdf8;
    --info-soft: rgba(56, 189, 248, 0.14);
    --code-bg: #020617;
    --code-border: #1f2937;
  }

  html, body {
    background: var(--bg);
    color: var(--text);
  }

  body {
    direction: rtl;
    text-align: right;
    font-family: "Tahoma", "Arial", "Segoe UI", sans-serif;
    line-height: 1.9;
    max-width: 1080px;
    margin: 0 auto;
    padding: 28px;
  }

  * {
    box-sizing: border-box;
  }

  h1, h2, h3, h4 {
    color: #f8fafc;
    line-height: 1.55;
  }

  h1 {
    font-size: 2.15rem;
    margin: 0 0 14px;
    letter-spacing: -0.5px;
  }

  h2 {
    margin-top: 52px;
    padding: 14px 18px;
    border-radius: 18px;
    background: linear-gradient(90deg, rgba(85, 130, 255, 0.24), rgba(30, 41, 59, 0.25));
    border: 1px solid var(--border);
    border-right: 7px solid var(--primary);
  }

  h3 {
    margin-top: 34px;
    color: #dbeafe;
    border-bottom: 1px solid var(--border);
    padding-bottom: 8px;
  }

  h4 {
    color: #bfdbfe;
    margin-top: 24px;
  }

  p, li {
    font-size: 1.02rem;
  }

  a {
    color: var(--primary-2);
    text-decoration: none;
  }

  a:hover {
    text-decoration: underline;
  }

  hr {
    border: none;
    height: 1px;
    background: var(--border);
    margin: 34px 0;
  }

  code {
    direction: ltr;
    unicode-bidi: plaintext;
    background: rgba(148, 163, 184, 0.16);
    color: #f8fafc;
    padding: 2px 7px;
    border-radius: 7px;
    font-size: 0.95em;
  }

  pre {
    direction: ltr;
    text-align: left;
    background: var(--code-bg);
    color: #e5e7eb;
    padding: 18px;
    border-radius: 18px;
    overflow-x: auto;
    line-height: 1.75;
    border: 1px solid var(--code-border);
    box-shadow: 0 14px 35px rgba(0,0,0,0.28);
  }

  pre code {
    background: transparent;
    padding: 0;
    color: inherit;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin: 22px 0;
    overflow: hidden;
    border-radius: 16px;
    border: 1px solid var(--border);
  }

  th {
    background: rgba(85, 130, 255, 0.35);
    color: #f8fafc;
    font-weight: 700;
  }

  td, th {
    border: 1px solid var(--border);
    padding: 12px 14px;
    vertical-align: top;
  }

  tr:nth-child(even) td {
    background: rgba(30, 41, 59, 0.55);
  }

  tr:nth-child(odd) td {
    background: rgba(15, 23, 42, 0.75);
  }

  .cover {
    background:
      radial-gradient(circle at top left, rgba(85, 130, 255, 0.32), transparent 32%),
      linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(2, 6, 23, 0.98));
    border: 1px solid var(--border);
    border-radius: 28px;
    padding: 34px 32px;
    margin-bottom: 28px;
    box-shadow: 0 24px 70px rgba(0,0,0,0.32);
  }

  .subtitle {
    color: var(--muted);
    font-size: 1.08rem;
    max-width: 880px;
    margin-top: 10px;
  }

  .meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
  }

  .pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(85, 130, 255, 0.15);
    color: #dbeafe;
    border: 1px solid rgba(125, 160, 255, 0.35);
    font-size: 0.92rem;
    font-weight: 700;
  }

  .toc {
    background: rgba(30, 41, 59, 0.72);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 22px 24px;
    margin: 28px 0 36px;
  }

  .toc h2 {
    margin-top: 0;
    background: transparent;
    border: none;
    padding: 0;
  }

  .toc ol {
    margin: 0;
    padding-right: 24px;
  }

  .toc li {
    margin: 8px 0;
    color: var(--muted);
  }

  .card {
    background: rgba(30, 41, 59, 0.72);
    border: 1px solid var(--border);
    border-radius: 22px;
    padding: 18px 20px;
    margin: 20px 0;
    box-shadow: 0 12px 32px rgba(0,0,0,0.18);
  }

  .note {
    border-right: 7px solid var(--primary);
    background: var(--primary-soft);
  }

  .success {
    border-right: 7px solid var(--success);
    background: var(--success-soft);
  }

  .warning {
    border-right: 7px solid var(--warning);
    background: var(--warning-soft);
  }

  .danger {
    border-right: 7px solid var(--danger);
    background: var(--danger-soft);
  }

  .info {
    border-right: 7px solid var(--info);
    background: var(--info-soft);
  }

  .card-title {
    font-weight: 800;
    color: #f8fafc;
    margin-bottom: 6px;
  }

  .flow {
    display: grid;
    gap: 12px;
    margin: 22px 0;
  }

  .step {
    display: grid;
    grid-template-columns: 54px 1fr;
    gap: 14px;
    align-items: start;
    background: rgba(15, 23, 42, 0.72);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 14px;
  }

  .step-no {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(85, 130, 255, 0.22);
    color: #dbeafe;
    font-weight: 900;
    border: 1px solid rgba(125, 160, 255, 0.35);
  }

  .step strong {
    color: #f8fafc;
  }

  .muted {
    color: var(--muted);
  }

  .kbd {
    direction: ltr;
    display: inline-block;
    border: 1px solid var(--border);
    border-bottom-width: 3px;
    border-radius: 8px;
    padding: 0 7px;
    background: rgba(2, 6, 23, 0.72);
    color: #f8fafc;
    font-family: monospace;
  }

  .compare {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    margin: 20px 0;
  }

  @media (max-width: 760px) {
    body {
      padding: 18px;
    }

    .compare {
      grid-template-columns: 1fr;
    }
  }

  .footer {
    margin-top: 60px;
    padding: 22px;
    border-radius: 22px;
    background: rgba(2, 6, 23, 0.72);
    border: 1px solid var(--border);
    color: var(--muted);
  }
</style>

<div dir="rtl">

<div class="cover">

# دليل Idempotency في Laravel APIs

<p class="subtitle">
شرح هندسي مبسّط واحترافي لفكرة Idempotency، ولماذا نحتاجها، وما علاقة الكاش والأقفال بها، وكيف يتكامل معها Flutter، وما حدودها في العمليات الحساسة مثل شراء الاختبارات.
</p>

<div class="meta">
  <span class="pill">Laravel Middleware</span>
  <span class="pill">Redis Cache</span>
  <span class="pill">Distributed Locks</span>
  <span class="pill">Flutter + Dio</span>
  <span class="pill">Nerd Graduation Project</span>
</div>

</div>

<div class="toc">

## الفهرس

<ol>
  <li><a href="#section-1">ما هي Idempotency؟</a></li>
  <li><a href="#section-2">المشكلة التي تحلها</a></li>
  <li><a href="#section-3">مثال واقعي: شراء اختبار</a></li>
  <li><a href="#section-4">لماذا لا يكفي منع الضغط المتكرر في Flutter؟</a></li>
  <li><a href="#section-5">ما هي الحالات الحساسة؟</a></li>
  <li><a href="#section-6">ما هو Idempotency-Key؟</a></li>
  <li><a href="#section-7">لماذا نحتاج Lock؟ وعلى ماذا يكون القفل؟</a></li>
  <li><a href="#section-8">ما فائدة الكاش؟</a></li>
  <li><a href="#section-9">الفرق بين Lock و Cached Response</a></li>
  <li><a href="#section-10">هيكلية طبقة الحماية الكاملة</a></li>
  <li><a href="#section-11">شرح Middleware هندسيًا</a></li>
  <li><a href="#section-12">تكامل Flutter مع Idempotency</a></li>
  <li><a href="#section-13">التعامل مع 409 Conflict</a></li>
  <li><a href="#section-14">الحالة الحرجة: انتهاء مدة الكاش</a></li>
  <li><a href="#section-15">هل Idempotency بديل عن قاعدة البيانات؟</a></li>
  <li><a href="#section-16">متى نستخدم جدول دائم بدل Cache؟</a></li>
  <li><a href="#section-17">أفضل ممارسة في مشروع Nerd</a></li>
  <li><a href="#section-18">الخلاصة النهائية</a></li>
</ol>

</div>

---

<h2 id="section-1">1. ما هي Idempotency؟</h2>

Idempotency تعني أن **نفس العملية إذا أُرسلت أكثر من مرة بنفس المفتاح، لا يجب أن تُنفّذ أكثر من مرة**.

بمعنى أبسط:

<div class="card note">
  <div class="card-title">الفكرة الأساسية</div>
  إذا أرسل Flutter نفس الطلب مرتين بسبب timeout أو retry أو ضعف الإنترنت، يجب أن يفهم Laravel أن الطلب الثاني هو إعادة لنفس العملية، وليس عملية جديدة.
</div>

مثال:

```http
POST /api/v1/tests/10/purchase
Idempotency-Key: 6b3c1c0e-238b-4e3b-8b6e-2eaf4c5a101a
```

إذا وصل الطلب أول مرة، يتم تنفيذه.

إذا وصل مرة ثانية بنفس المفتاح، لا يتم تنفيذ الشراء مرة ثانية، بل يتم إرجاع نفس النتيجة السابقة أو إعلام الفرونت أن الطلب ما زال قيد المعالجة.

---

<h2 id="section-2">2. المشكلة التي تحلها</h2>

المشكلة لا تحدث فقط عندما يضغط المستخدم الزر مرتين.

المشكلة الأخطر تحدث عندما:

```text
الطلب يصل إلى السيرفر
السيرفر ينفذه بنجاح
لكن الرد لا يصل إلى Flutter
```

من وجهة نظر Flutter:

```text
أنا لم أستلم response، إذًا ربما العملية فشلت.
```

فيعيد إرسال الطلب.

لكن من وجهة نظر Laravel:

```text
أنا نفذت العملية بالفعل.
```

بدون Idempotency قد يتم تنفيذ العملية مرة ثانية، وهذا خطير في العمليات التي تنشئ سجلات أو تغيّر أموالًا أو نتائج أو إحصائيات.

---

<h2 id="section-3">3. مثال واقعي: شراء اختبار</h2>

<h3>3.1 بدون Idempotency</h3>

<div class="flow">
  <div class="step">
    <div class="step-no">1</div>
    <div><strong>المستخدم يضغط زر شراء الاختبار.</strong><br><span class="muted">Flutter يرسل طلب الشراء إلى Laravel.</span></div>
  </div>
  <div class="step">
    <div class="step-no">2</div>
    <div><strong>Laravel ينفذ الشراء.</strong><br><span class="muted">يتم إنشاء سجل في جدول المشتريات.</span></div>
  </div>
  <div class="step">
    <div class="step-no">3</div>
    <div><strong>الرد لا يصل إلى Flutter.</strong><br><span class="muted">بسبب ضعف الإنترنت أو timeout.</span></div>
  </div>
  <div class="step">
    <div class="step-no">4</div>
    <div><strong>Flutter يعيد الطلب.</strong><br><span class="muted">Laravel قد ينفذ الشراء مرة ثانية.</span></div>
  </div>
</div>

النتائج المحتملة:

- إنشاء purchase مكرر.
- خصم رصيد مرتين.
- إرسال إشعارين.
- تحديث summary tables مرتين.
- مشاكل في التقارير والإحصائيات.

<h3>3.2 مع Idempotency</h3>

```text
1. Flutter يولد Idempotency-Key.
2. يرسل الطلب مع المفتاح.
3. Laravel ينفذ العملية أول مرة.
4. Laravel يحفظ response مؤقتًا.
5. إذا تكرر نفس الطلب بنفس المفتاح، يرجع نفس response.
6. لا يتم تنفيذ الشراء مرة ثانية.
```

<div class="card success">
  <div class="card-title">النتيجة</div>
  العملية تصبح آمنة ضد التكرار الناتج عن مشاكل الشبكة أو retry.
</div>

---

<h2 id="section-4">4. لماذا لا يكفي منع الضغط المتكرر في Flutter؟</h2>

منع الضغط المتكرر في Flutter مهم، لكنه ليس كافيًا.

لأن التكرار قد يحدث بدون أن يضغط المستخدم مرتين أصلًا.

أسباب التكرار:

| السبب | التوضيح |
|---|---|
| Timeout | الطلب نُفذ في السيرفر لكن Flutter لم يستلم الرد |
| ضعف الإنترنت | الرد ضاع أو تأخر |
| Retry تلقائي | بعض الطبقات قد تعيد المحاولة |
| ضغط سريع | المستخدم يضغط قبل تحديث حالة الزر |
| إغلاق التطبيق | المستخدم يعيد العملية بعد فتح التطبيق |
| Network delay | طلبان يصلان بترتيب غير متوقع |

<div class="card warning">
  <div class="card-title">قاعدة مهمة</div>
  Flutter يحسن تجربة المستخدم، لكن Laravel يجب أن يحمي البيانات.
</div>

---

<h2 id="section-5">5. ما هي الحالات الحساسة؟</h2>

الحالة الحساسة هي أي API إذا تكرر تنفيذها بالخطأ قد تسبب ضررًا أو بيانات غير صحيحة.

| العملية | درجة الحاجة | السبب |
|---|---:|---|
| شراء اختبار | عالية جدًا | منع الشراء أو الخصم المكرر |
| بدء جلسة امتحان | عالية | منع إنشاء أكثر من جلسة |
| تسليم إجابات نهائية | عالية جدًا | منع التصحيح أو الاحتساب مرتين |
| إنشاء مراجعة | عالية | منع تكرار المراجعة |
| إرسال OTP | متوسطة إلى عالية | منع الإزعاج وتكرار الرموز |
| إنشاء بلاغ | عالية | منع البلاغات المكررة |
| طلب تحقق أكاديمي | عالية | منع أكثر من طلب لنفس العملية |
| Like / Unlike | متوسطة | مفيد للتوحيد، لكن غالبًا توجد حماية من قاعدة البيانات |
| GET Test Details | منخفضة | قراءة فقط ولا تغيّر البيانات |

---

<h2 id="section-6">6. ما هو Idempotency-Key؟</h2>

هو مفتاح يرسله الفرونت مع الطلب ليقول للسيرفر:

> هذه العملية لها رقم خاص. إذا رأيت نفس الرقم مرة ثانية، فاعتبرها نفس العملية.

مثال:

```http
Idempotency-Key: 38f4b3fa-76f1-42ea-997e-f731c1a7c812
```

<h3>قاعدة الاستخدام</h3>

| الحالة | ماذا يفعل Flutter؟ |
|---|---|
| عملية جديدة | يولد مفتاحًا جديدًا |
| Retry لنفس العملية | يستخدم نفس المفتاح |
| عملية مختلفة | يولد مفتاحًا مختلفًا |

مثال صحيح:

```text
أول محاولة شراء:
Idempotency-Key: A

Retry لنفس الشراء:
Idempotency-Key: A
```

مثال خاطئ:

```text
أول محاولة شراء:
Idempotency-Key: A

Retry لنفس الشراء:
Idempotency-Key: B
```

المثال الثاني خاطئ لأن Laravel سيعتبر المفتاح الجديد عملية جديدة.

---

<h2 id="section-7">7. لماذا نحتاج Lock؟ وعلى ماذا يكون القفل؟</h2>

الكاش وحده لا يكفي.

لنفترض أن نفس الطلب وصل مرتين بنفس اللحظة تقريبًا:

```text
الطلب الأول وصل: 10:00:00.001
الطلب الثاني وصل: 10:00:00.002
```

الطلب الأول يبحث في الكاش:

```text
لا يوجد response محفوظ.
```

الطلب الثاني يبحث في الكاش قبل انتهاء الأول:

```text
لا يوجد response محفوظ.
```

بدون Lock، الطلبان سيدخلان إلى Controller وسينفذان العملية.

<h3>ما هو Lock؟</h3>

الـ Lock هو قفل مؤقت يقول:

> هذه العملية قيد التنفيذ الآن، لا تسمح لنسخة ثانية من نفس العملية بالدخول.

في Laravel:

```php
$lock = Cache::lock($lockKey, 10);
```

أي قفل لمدة 10 ثوانٍ.

<h3>القفل يكون على ماذا؟</h3>

القفل لا يكون على كل النظام، ولا على كل المستخدمين، ولا على الجدول كاملًا.

القفل يكون على بصمة العملية.

البصمة تتكون من:

```text
user_id + HTTP method + route_name + Idempotency-Key
```

مثال:

```text
15|POST|tests.purchase|abc-123
```

هذا يعني:

> المستخدم 15 ينفذ عملية شراء بنفس المفتاح abc-123.

أي طلب آخر بنفس هذه البصمة يعتبر نفس العملية.

<div class="card info">
  <div class="card-title">تشبيه بسيط</div>
  Idempotency-Key مثل رقم الطلب في مطعم.  
  Lock يعني أن الطلب قيد التحضير الآن.  
  Cached Response يعني أن الطلب انتهى ويمكن إعطاء نفس النتيجة بدل تحضير طلب جديد.
</div>

---

<h2 id="section-8">8. ما فائدة الكاش؟</h2>

الكاش في هذا التصميم له وظيفتان:

<h3>8.1 حفظ response النهائي</h3>

بعد تنفيذ العملية، نحفظ الرد لفترة محددة:

```php
Cache::put($responseCacheKey, [
    'content' => $response->getContent(),
    'status' => $response->getStatusCode(),
    'headers' => [
        'Content-Type' => 'application/json',
    ],
], now()->addMinutes(10));
```

إذا أرسل Flutter نفس الطلب بنفس المفتاح خلال هذه المدة، يرجع Laravel نفس الرد بدون تنفيذ العملية.

<h3>8.2 إنشاء Lock</h3>

Laravel يستخدم cache store مثل Redis لإنشاء lock مشترك بين requests.

```php
$lock = Cache::lock($lockKey, 10);
```

Redis مناسب جدًا لأنه سريع ويدعم الأقفال المؤقتة.

---

<h2 id="section-9">9. الفرق بين Lock و Cached Response</h2>

| الأداة | متى تعمل؟ | ماذا تمنع؟ |
|---|---|---|
| Lock | أثناء تنفيذ الطلب الأول | تمنع دخول طلب ثانٍ بنفس اللحظة |
| Cached Response | بعد انتهاء الطلب الأول | تمنع إعادة تنفيذ طلب انتهى سابقًا |

<div class="compare">
  <div class="card warning">
    <div class="card-title">بدون Lock</div>
    إذا وصل طلبان بنفس اللحظة، قد لا يجد أي منهما response محفوظًا، فيدخلان معًا إلى التنفيذ.
  </div>
  <div class="card success">
    <div class="card-title">بدون Cached Response</div>
    إذا انتهى الطلب الأول ثم جاء الثاني لاحقًا، لن يجد نتيجة محفوظة وقد ينفذ من جديد.
  </div>
</div>

لذلك التصميم القوي يحتاج الاثنين معًا.

---

<h2 id="section-10">10. هيكلية طبقة الحماية الكاملة</h2>

Idempotency ليست طبقة وحيدة. هي جزء من منظومة حماية.

<div class="flow">
  <div class="step">
    <div class="step-no">1</div>
    <div><strong>Flutter UI Guard</strong><br><span class="muted">تعطيل الزر أثناء التحميل ومنع الضغط المتكرر.</span></div>
  </div>
  <div class="step">
    <div class="step-no">2</div>
    <div><strong>Idempotency Middleware</strong><br><span class="muted">منع تكرار نفس request بسبب retry أو timeout.</span></div>
  </div>
  <div class="step">
    <div class="step-no">3</div>
    <div><strong>Service Business Rules</strong><br><span class="muted">التأكد من أن العملية منطقية، مثل عدم شراء اختبار مملوك مسبقًا.</span></div>
  </div>
  <div class="step">
    <div class="step-no">4</div>
    <div><strong>Database Transaction</strong><br><span class="muted">تنفيذ التعديلات كوحدة واحدة متناسقة.</span></div>
  </div>
  <div class="step">
    <div class="step-no">5</div>
    <div><strong>Unique Indexes / Constraints</strong><br><span class="muted">منع التكرار بشكل دائم على مستوى قاعدة البيانات.</span></div>
  </div>
  <div class="step">
    <div class="step-no">6</div>
    <div><strong>Events + Queue</strong><br><span class="muted">تنفيذ الآثار الثانوية مثل summary tables والإشعارات.</span></div>
  </div>
</div>

<div class="card danger">
  <div class="card-title">تنبيه هندسي</div>
  لا تعتمد على Idempotency Middleware وحده في العمليات الحساسة جدًا. يجب أن تبقى قواعد العمل والـ constraints موجودة داخل Service وقاعدة البيانات.
</div>

---

<h2 id="section-11">11. شرح Middleware هندسيًا</h2>

تسلسل عمل Middleware:

```text
1. إذا كان الطلب GET، لا تطبق Idempotency.
2. اقرأ Idempotency-Key من الهيدر.
3. إذا لا يوجد مفتاح، مرر الطلب عاديًا.
4. أنشئ fingerprint من المستخدم + method + route + key.
5. افحص هل يوجد response محفوظ.
6. إذا وجد response، أعده فورًا.
7. إذا لا يوجد response، حاول أخذ lock.
8. إذا لم تستطع أخذ lock، فهذا يعني أن نفس الطلب قيد التنفيذ.
9. إذا أخذت lock، مرر الطلب إلى Controller.
10. بعد رجوع response، خزنه في cache.
11. حرر lock داخل finally.
```

<h3>أهم أجزاء الكود</h3>

قراءة المفتاح:

```php
$idempotencyKey = $request->header('Idempotency-Key');
```

بناء البصمة:

```php
$fingerprint = sha1(
    $userId . '|' .
    $request->method() . '|' .
    $routeName . '|' .
    $idempotencyKey
);
```

مفاتيح الكاش:

```php
$responseCacheKey = 'idempotency:response:' . $fingerprint;
$lockKey = 'idempotency:lock:' . $fingerprint;
```

البحث عن رد سابق:

```php
$cachedResponse = Cache::get($responseCacheKey);
```

إنشاء قفل:

```php
$lock = Cache::lock($lockKey, 10);
```

حفظ الرد:

```php
Cache::put($responseCacheKey, [
    'content' => $response->getContent(),
    'status' => $response->getStatusCode(),
    'headers' => [
        'Content-Type' => 'application/json',
    ],
], now()->addMinutes(10));
```

تحرير القفل:

```php
finally {
    optional($lock)->release();
}
```

---

<h2 id="section-12">12. تكامل Flutter مع Idempotency</h2>

في Flutter، يجب توليد مفتاح لكل عملية حساسة.

مثال باستخدام Dio:

```dart
final idempotencyKey = const Uuid().v4();

await dio.post(
  '/api/v1/tests/10/purchase',
  options: Options(
    headers: {
      'Idempotency-Key': idempotencyKey,
    },
  ),
);
```

عند retry لنفس العملية:

```dart
await dio.post(
  '/api/v1/tests/10/purchase',
  options: Options(
    headers: {
      'Idempotency-Key': idempotencyKey,
    },
  ),
);
```

لاحظ أننا استخدمنا نفس المفتاح.

<h3>قاعدة مهمة للفرونت</h3>

<div class="card note">
  <div class="card-title">قاعدة ذهبية</div>
  عملية جديدة = مفتاح جديد.  
  Retry لنفس العملية = نفس المفتاح.
</div>

---

<h2 id="section-13">13. التعامل مع 409 Conflict</h2>

إذا رجع السيرفر:

```http
409 Conflict
```

ومعه رسالة:

```json
{
  "success": false,
  "title": "! الطلب قيد المعالجة",
  "message": "تم استقبال نفس الطلب مسبقاً وما زال قيد المعالجة",
  "status_code": 409
}
```

فهذا لا يعني أن العملية فشلت نهائيًا.

بل يعني:

> نفس الطلب ما زال قيد التنفيذ.

يمكن لـ Flutter أن يتعامل هكذا:

```dart
if (statusCode == 409) {
  await Future.delayed(const Duration(milliseconds: 800));
  // Retry using the same Idempotency-Key
}
```

أو يبقي الزر في حالة loading حتى تنتهي العملية.

---

<h2 id="section-14">14. الحالة الحرجة: انتهاء مدة الكاش</h2>

السؤال المهم:

> إذا اشترى المستخدم اختبارًا، وتم تنفيذ العملية بنجاح، وحُفظ response في الكاش لمدة 10 دقائق، ثم بعد 15 دقيقة أرسل نفس API بنفس المفتاح، هل سيتجاوز Middleware؟

الجواب:

<div class="card danger">
  <div class="card-title">نعم، إذا انتهى الكاش</div>
  إذا انتهت مدة حفظ response من الكاش، فلن يجد Middleware النتيجة القديمة، وقد يمرر الطلب للـ Controller من جديد.
</div>

وهذا منطقي إذا كان تصميمك يعتبر Idempotency مؤقتة.

لكن في العمليات الحساسة مثل الشراء، لا يجوز الاعتماد على الكاش وحده.

يجب أن تكون الحماية الدائمة في Service وقاعدة البيانات.

مثال:

```php
$existingPurchase = DB::table('test_purchases')
    ->where('buyer_user_id', $userId)
    ->where('test_id', $testId)
    ->where('payment_status', 'paid')
    ->first();

if ($existingPurchase) {
    return [
        'already_purchased' => true,
        'purchase_id' => $existingPurchase->id,
    ];
}
```

أي حتى لو انتهى الكاش، لن يتم إنشاء شراء جديد.

---

<h2 id="section-15">15. هل Idempotency بديل عن قاعدة البيانات؟</h2>

لا.

Idempotency تحميك من تكرار الطلب على مستوى الشبكة والـ retry.

لكن قاعدة البيانات تحميك من التكرار النهائي.

مثال في الشراء:

```php
$table->unique(['buyer_user_id', 'test_id']);
```

أو إذا عندك حالات دفع متعددة، قد تحتاج تصميمًا أدق حسب منطق المشروع.

<div class="card success">
  <div class="card-title">الصيغة الصحيحة</div>
  Idempotency تمنع التكرار القريب.  
  Business Rules تمنع التكرار المنطقي.  
  Database Constraints تمنع التكرار النهائي.
</div>

---

<h2 id="section-16">16. متى نستخدم جدول دائم بدل Cache؟</h2>

في العمليات الحساسة جدًا، مثل الدفع أو إنشاء موارد مالية، الأفضل استخدام جدول دائم.

مثال جدول:

```text
idempotency_keys
```

أعمدته:

| العمود | الفائدة |
|---|---|
| user_id | صاحب الطلب |
| method | نوع الطلب |
| route_name | اسم الراوت |
| idempotency_key | المفتاح القادم من الفرونت |
| request_hash | بصمة body الطلب |
| response_body | الرد المحفوظ |
| status_code | كود الرد |
| expires_at | تاريخ انتهاء صلاحية المفتاح |
| created_at | تاريخ الإنشاء |

هذا يعطيك تتبعًا أقوى من Redis Cache.

<h3>متى أختار الجدول الدائم؟</h3>

| نوع العملية | Cache يكفي؟ | جدول دائم أفضل؟ |
|---|---:|---:|
| Like / Unlike | نعم غالبًا | لا غالبًا |
| إرسال OTP | نعم غالبًا | حسب السياسة |
| إنشاء مراجعة | ممكن | ممكن |
| شراء اختبار داخلي | ممكن مع DB constraints | نعم إذا العملية مالية |
| Payment Gateway | لا يفضّل وحده | نعم جدًا |
| Submit Exam | حسب الأهمية | نعم إذا التصحيح حساس جدًا |

---

<h2 id="section-17">17. أفضل ممارسة في مشروع Nerd</h2>

ينصح باستخدام Idempotency في هذه المسارات:

```text
POST   /api/v1/tests/{id}/purchase
POST   /api/v1/tests/{id}/exam-sessions
POST   /api/v1/exam-sessions/{id}/submit
POST   /api/v1/tests/{id}/reviews
POST   /api/v1/auth/resend-otp
POST   /api/v1/verification-requests
POST   /api/v1/reports
POST   /api/v1/tests/{id}/like
DELETE /api/v1/tests/{id}/like
```

لكن مع الانتباه:

<div class="card warning">
  <div class="card-title">لا تضع كل الثقة في Middleware</div>
  في كل API حساسة، اكتب Business Rule داخل Service تمنع التكرار حتى لو انتهى الكاش أو لم يصل Idempotency-Key.
</div>

---

<h2 id="section-18">18. الخلاصة النهائية</h2>

| المفهوم | المعنى |
|---|---|
| Idempotency-Key | رقم العملية الذي يرسله Flutter |
| Fingerprint | بصمة مبنية من المستخدم والراوت والميثود والمفتاح |
| Lock | يمنع نفس العملية من الدخول مرتين بنفس اللحظة |
| Cached Response | يرجع نفس النتيجة إذا تكرر الطلب بعد نجاحه |
| Business Rules | تمنع تكرار العملية منطقيًا داخل Service |
| DB Constraints | تمنع التكرار نهائيًا في قاعدة البيانات |

<div class="card success">
  <div class="card-title">الجملة الأهم</div>
  Idempotency تمنع تكرار الطلب بسبب مشاكل الشبكة والـ retry، أما قواعد العمل وقيود قاعدة البيانات فتمنع تكرار الأثر بشكل دائم.
</div>

<div class="footer">
  هذا الملف مصمم ليكون مرجعًا هندسيًا مختصرًا وواضحًا عند تطبيق Idempotency في APIs الخاصة بمشروع Nerd، خصوصًا العمليات الحساسة مثل الشراء، جلسات الامتحان، تسليم الإجابات، والمراجعات.
</div>

</div>
