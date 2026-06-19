<style>
  :root {
    color-scheme: dark;
    --primary: #7dd3fc;
    --primary-strong: #38bdf8;
    --primary-soft: rgba(56, 189, 248, 0.12);
    --success: #86efac;
    --success-soft: rgba(34, 197, 94, 0.13);
    --warning: #fbbf24;
    --warning-soft: rgba(251, 191, 36, 0.14);
    --danger: #fca5a5;
    --danger-soft: rgba(248, 113, 113, 0.13);
    --muted: #94a3b8;
    --border: #334155;
    --border-soft: rgba(148, 163, 184, 0.25);
    --bg: #020617;
    --surface: #0f172a;
    --surface-2: #111827;
    --surface-3: #1e293b;
    --text: #e5e7eb;
    --text-strong: #f8fafc;
    --code-bg: #020617;
    --code-text: #d1d5db;
    --shadow: 0 18px 45px rgba(0, 0, 0, 0.35);
  }

  body {
    direction: rtl;
    text-align: right;
    font-family: "Tahoma", "Arial", sans-serif;
    line-height: 1.9;
    color: var(--text);
    background:
      radial-gradient(circle at top right, rgba(56, 189, 248, 0.16), transparent 28%),
      radial-gradient(circle at top left, rgba(129, 140, 248, 0.12), transparent 26%),
      var(--bg);
    padding: 24px;
  }

  h1, h2, h3, h4 {
    color: var(--text-strong);
    line-height: 1.5;
  }

  h1 {
    border-bottom: 3px solid var(--primary-strong);
    padding-bottom: 12px;
    margin-bottom: 24px;
    text-shadow: 0 0 18px rgba(56, 189, 248, 0.22);
  }

  h2 {
    margin-top: 42px;
    padding: 12px 16px;
    background: linear-gradient(90deg, rgba(56, 189, 248, 0.06), var(--primary-soft));
    border: 1px solid var(--border-soft);
    border-right: 5px solid var(--primary-strong);
    border-radius: 12px;
    box-shadow: var(--shadow);
  }

  h3 {
    margin-top: 30px;
    border-right: 4px solid var(--border);
    padding-right: 10px;
    color: #bae6fd;
  }

  h4 {
    color: #c4b5fd;
  }

  a {
    color: var(--primary);
  }

  .note, .warning, .danger, .success {
    padding: 14px 16px;
    border-radius: 12px;
    margin: 16px 0;
    border: 1px solid var(--border-soft);
    box-shadow: var(--shadow);
  }

  .note {
    background: rgba(15, 23, 42, 0.86);
    border-right: 5px solid var(--muted);
  }

  .warning {
    background: var(--warning-soft);
    border-right: 5px solid var(--warning);
  }

  .danger {
    background: var(--danger-soft);
    border-right: 5px solid var(--danger);
  }

  .success {
    background: var(--success-soft);
    border-right: 5px solid var(--success);
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin: 18px 0;
    font-size: 14px;
    background: rgba(15, 23, 42, 0.78);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow);
  }

  th, td {
    border: 1px solid var(--border);
    padding: 10px;
    vertical-align: top;
  }

  th {
    background: #1e293b;
    color: var(--text-strong);
    font-weight: bold;
  }

  tr:nth-child(even) td {
    background: rgba(30, 41, 59, 0.45);
  }

  code {
    direction: ltr;
    unicode-bidi: embed;
    background: #172033;
    color: #fef3c7;
    border: 1px solid rgba(148, 163, 184, 0.18);
    padding: 2px 6px;
    border-radius: 6px;
    font-family: "Consolas", "Courier New", monospace;
  }

  pre {
    direction: ltr;
    text-align: left;
    background: var(--code-bg);
    color: var(--code-text);
    padding: 16px;
    border-radius: 12px;
    overflow-x: auto;
    line-height: 1.6;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
  }

  pre code {
    background: transparent;
    color: inherit;
    padding: 0;
    border: none;
  }

  blockquote {
    margin: 18px 0;
    padding: 10px 16px;
    background: rgba(15, 23, 42, 0.7);
    border-right: 4px solid var(--primary-strong);
    border-radius: 10px;
    color: #cbd5e1;
  }

  .toc {
    background: rgba(15, 23, 42, 0.9);
    border: 1px solid var(--border-soft);
    border-radius: 14px;
    padding: 18px 24px;
    margin: 20px 0 32px;
    box-shadow: var(--shadow);
  }

  .toc a {
    color: var(--primary);
    text-decoration: none;
  }

  .toc a:hover {
    text-decoration: underline;
  }

  .small {
    color: var(--muted);
    font-size: 13px;
  }

  hr {
    border: none;
    border-top: 1px solid var(--border);
    margin: 28px 0;
  }
</style>

# توثيق تكامل جداول مراجعة الاختبارات في لوحة التحكم

<div class="note">
هذا المستند يشرح آلية عمل وتكامل جداول <code>test</code> و <code>test_review_rounds</code> و <code>test_status_histories</code> ضمن APIs لوحة التحكم الخاصة بقسم <strong>Test Management</strong>، وتحديدًا APIs: الموافقة على النشر، حذف اختبار، وطلب تعديلات.
</div>

## الفهرس

<div class="toc">

1. [الهدف من المستند](#الهدف-من-المستند)  
2. [الجداول الأساسية ودور كل جدول](#الجداول-الأساسية-ودور-كل-جدول)  
3. [قيم الـ Enum المعتمدة](#قيم-الـ-enum-المعتمدة)  
4. [مبدأ الحالة الحالية مقابل التاريخ](#مبدأ-الحالة-الحالية-مقابل-التاريخ)  
5. [مبدأ جولات المراجعة test_review_rounds](#مبدأ-جولات-المراجعة-test_review_rounds)  
6. [API الموافقة على نشر اختبار](#api-الموافقة-على-نشر-اختبار)  
7. [API حذف اختبار](#api-حذف-اختبار)  
8. [API طلب تعديلات على اختبار](#api-طلب-تعديلات-على-اختبار)  
9. [قواعد التعامل مع current_approval_version و based_on_approval_version](#قواعد-التعامل-مع-current_approval_version-و-based_on_approval_version)  
10. [قواعد التزامن Race Conditions](#قواعد-التزامن-race-conditions)  
11. [تحديث الواجهة عبر Laravel Reverb](#تحديث-الواجهة-عبر-laravel-reverb)  
12. [تحديث جداول Summary](#تحديث-جداول-summary)  
13. [خلاصة الحالات](#خلاصة-الحالات)

</div>

---

## الهدف من المستند

الهدف هو أن تستطيع أي أداة ذكاء اصطناعي أو مطور جديد فهم كيف تعمل دورة مراجعة الاختبارات العامة داخل لوحة التحكم، وكيف تتكامل الجداول التالية مع بعضها:

- <code>test</code>
- <code>test_review_rounds</code>
- <code>test_status_histories</code>
- <code>test_revision_requests</code>
- <code>test_revision_change_logs</code>

مع التركيز على APIs الثلاثة:

1. الموافقة على نشر اختبار.
2. حذف اختبار.
3. طلب تعديلات على اختبار.

---

## الجداول الأساسية ودور كل جدول

### 1. جدول test

يمثل الاختبار نفسه والحالة الحالية له.

أهم الحقول المتعلقة بدورة المراجعة:

| الحقل | المعنى |
|---|---|
| <code>review_status</code> | الحالة الحالية للاختبار. هذا هو مصدر الحقيقة للحالة الحالية. |
| <code>current_approval_version</code> | رقم آخر نسخة منشورة للعامة. يبدأ من 0 قبل أول نشر. |
| <code>published_at</code> | تاريخ أول ظهور/نشر فعلي للعامة، ويُستخدم لإحصائيات النشر. |
| <code>deleted_at</code> | يستخدم فقط في حالة soft delete. |
| <code>test_type</code> | عام أو خاص. الاختبار الخاص لا يدخل دورة مراجعة لوحة التحكم. |
| <code>price</code> | يستخدم لتحديد هل الاختبار مدفوع. |

<div class="warning">
الاختبار الخاص لا يظهر في لوحة Test Management ولا يمر بدورة مراجعة إدارية.
</div>

### 2. جدول test_review_rounds

يمثل جولة مراجعة إدارية واحدة.

الجولة قد تكون:

- أول إرسال للاختبار العام.
- إعادة إرسال من صاحب الاختبار بعد تعديلات.
- جولة بسبب بلاغات تلقائية على نسخة منشورة.

أهم الحقول:

| الحقل | المعنى |
|---|---|
| <code>test_id</code> | الاختبار المرتبط بهذه الجولة. |
| <code>round_no</code> | رقم الجولة داخل الاختبار. |
| <code>reviewer_user_id</code> | المشرف الذي اتخذ القرار. يكون null قبل القرار. |
| <code>trigger_type</code> | سبب إنشاء الجولة. |
| <code>decision</code> | القرار النهائي للجولة. يبدأ pending. |
| <code>based_on_approval_version</code> | النسخة المنشورة التي بُنيت عليها الجولة، مهم جدًا في البلاغات. |
| <code>started_at</code> | وقت بداية الجولة. |
| <code>decided_at</code> | وقت اتخاذ القرار النهائي. |

### 3. جدول test_status_histories

يمثل سجل انتقالات الحالة.

هذا الجدول لا يحدد الحالة الحالية وحده، بل يسجل التاريخ. الحالة الحالية موجودة في <code>test.review_status</code>.

| الحقل | المعنى |
|---|---|
| <code>test_id</code> | الاختبار. |
| <code>test_review_round_id</code> | الجولة التي سببت انتقال الحالة، وقد يكون null في بعض الحالات القديمة أو الخاصة. |
| <code>from_status</code> | الحالة السابقة. يمكن أن تكون null عند أول سجل. |
| <code>to_status</code> | الحالة الجديدة. |
| <code>changed_by_user_id</code> | من غيّر الحالة. |
| <code>note</code> | ملاحظة أو سبب، مثل سبب الحذف أو وصف عام للقرار. |
| <code>created_at</code> | تاريخ حدوث الانتقال. |

### 4. جدول test_revision_requests

يمثل ما طلبه المشرف من تعديلات.

يُكتب فيه عند API طلب التعديلات فقط.

أهم الحقول:

| الحقل | المعنى |
|---|---|
| <code>test_review_round_id</code> | الجولة التي تتبع لها طلبات التعديل. |
| <code>test_id</code> | الاختبار. |
| <code>revision_type</code> | نوع التعديل المطلوب. |
| <code>target_question_id</code> | السؤال المستهدف، إن وجد. |
| <code>target_option_id</code> | الخيار المستهدف، إن وجد. |
| <code>created_by_user_id</code> | المشرف الذي طلب التعديل. |
| <code>resolved_at</code> | متى تم حل الطلب من قبل المستخدم لاحقًا. |
| <code>problem_note</code> | وصف المشكلة. |

### 5. جدول test_revision_change_logs

يمثل ماذا غيّر المستخدم فعليًا بعد أن استلم طلبات التعديل.

<div class="danger">
لا نكتب في <code>test_revision_change_logs</code> عند طلب المشرف للتعديلات. هذا الجدول يخص مرحلة تعديل المستخدم الفعلي ويخزن before_value و after_value.
</div>

---

## قيم الـ Enum المعتمدة

### TestReviewStatus في test.review_status و test_status_histories

| الاسم البرمجي | القيمة العربية | المعنى |
|---|---|---|
| <code>New</code> | <code>مسودة</code> | اختبار عام جديد بانتظار المراجعة. |
| <code>NeedsRevision</code> | <code>يحتاج تعديل</code> | المشرف طلب تعديلات من صاحب الاختبار. |
| <code>UnderReview</code> | <code>قيد المراجعة</code> | صاحب الاختبار عدّل وأعاد الإرسال. |
| <code>Approved</code> | <code>تم الموافقة عليه</code> | الاختبار منشور/مقبول. |
| <code>Deleted</code> | <code>تم حذفه</code> | الاختبار حُذف إداريًا. |
| <code>Reported</code> | <code>مبلغ عنه</code> | الاختبار دخل مراجعة بسبب بلاغات. |

### trigger_type في test_review_rounds

| القيمة | متى تستخدم؟ |
|---|---|
| <code>initial_submission</code> | أول مرة يرسل فيها المستخدم اختبارًا عامًا للمراجعة. |
| <code>owner_resubmission</code> | عندما يعيد صاحب الاختبار إرسال الاختبار بعد تعديلاته. |
| <code>auto_report</code> | عندما يصل الاختبار إلى حد بلاغات معين وينتقل إلى reported. |

### decision في test_review_rounds

| القيمة | المعنى |
|---|---|
| <code>pending</code> | الجولة مفتوحة ولم يتخذ المشرف قرارًا بعد. |
| <code>approved</code> | تم قبول الاختبار في هذه الجولة. |
| <code>needs_revision</code> | تم طلب تعديلات في هذه الجولة. |
| <code>deleted</code> | تم حذف الاختبار في هذه الجولة. |

---

## مبدأ الحالة الحالية مقابل التاريخ

الحالة الحالية للاختبار محفوظة في:

```text
 test.review_status
```

أما <code>test_status_histories</code> فهو سجل تاريخي لانتقالات الحالة.

مثال:

```text
مسودة -> يحتاج تعديل -> قيد المراجعة -> تم الموافقة عليه
```

في هذه الحالة:

```text
test.review_status = تم الموافقة عليه
```

لكن جدول <code>test_status_histories</code> يحتوي كل الانتقالات السابقة.

<div class="note">
لوحة Test Management اليومية تعتمد على آخر انتقال حالة مع الحالة الحالية، حتى لا يظهر نفس الاختبار في أكثر من يوم أو أكثر من عمود.
</div>

---

## مبدأ جولات المراجعة test_review_rounds

كل جولة تبدأ عادةً بـ:

```text
decision = pending
reviewer_user_id = null
decided_at = null
```

وعند قرار المشرف، لا ننشئ جولة جديدة للقرار نفسه، بل نغلق الجولة المفتوحة:

```text
reviewer_user_id = المشرف
decision = approved | needs_revision | deleted
decided_at = now()
```

<div class="success">
زر الموافقة، زر الحذف، وزر طلب التعديلات كلها تتعامل مع الجولة المفتوحة عند وجودها، ولا تنشئ جولة جديدة بمجرد اتخاذ القرار.
</div>

الجولة الجديدة تُنشأ فقط عند حدث جديد مستقل مثل:

- إعادة إرسال المستخدم بعد التعديلات.
- دخول الاختبار إلى reported بسبب البلاغات.
- أول إرسال للاختبار العام.

---

## API الموافقة على نشر اختبار

### الحالات المسموحة

يمكن الموافقة فقط إذا كانت حالة الاختبار:

- <code>مسودة</code>
- <code>قيد المراجعة</code>
- <code>مبلغ عنه</code>

### الحالات الممنوعة

| الحالة | السبب |
|---|---|
| <code>تم الموافقة عليه</code> | لا يمكن الموافقة عليه مرة أخرى. |
| <code>تم حذفه</code> | لا يمكن الموافقة على اختبار محذوف. |
| <code>يحتاج تعديل</code> | يجب أن يعدّل المستخدم ويعيد الإرسال أولًا. |
| اختبار خاص | لا يدخل دورة المراجعة. |

### التعامل مع test_review_rounds

عند الموافقة:

1. نبحث عن آخر جولة <code>pending</code> ونقفلها.
2. نحدثها هكذا:

```text
reviewer_user_id = reviewer.id
decision = approved
decided_at = now()
```

لا يتم إنشاء جولة جديدة عند الموافقة.

### التعامل مع test

نحدث:

```text
review_status = تم الموافقة عليه
```

أما <code>current_approval_version</code>:

| الحالة السابقة | هل نزيد current_approval_version؟ | السبب |
|---|---:|---|
| <code>مسودة</code> | نعم | أول نشر أو نشر بعد عدم وجود نسخة سابقة. |
| <code>قيد المراجعة</code> وكان version = 0 | نعم | أول نشر بعد تعديلات قبل أي نشر سابق. |
| <code>قيد المراجعة</code> وكان version > 0 | نعم | نسخة جديدة بعد تعديلات لاحقة. |
| <code>مبلغ عنه</code> | لا | البلاغ كان غير صحيح، ولا يوجد تغيير محتوى. |

### التعامل مع published_at

- إذا كان <code>current_approval_version = 0</code> قبل الموافقة، يتم ضبط <code>published_at = now()</code>.
- إذا كان منشورًا سابقًا، لا نغير <code>published_at</code> حتى تبقى الإحصائيات مرتبطة بتاريخ النشر الأول.

### التعامل مع test_status_histories

ننشئ سجلًا جديدًا:

```text
test_id = test.id
test_review_round_id = round.id
from_status = الحالة السابقة
to_status = تم الموافقة عليه
changed_by_user_id = reviewer.id
note = تمت الموافقة على نشر الاختبار من لوحة التحكم
```

### تحديث Summary Tables

نزيد عدادات النشر فقط إذا كان هذا أول نشر للعامة:

```text
old current_approval_version = 0
```

ولا نزيد العدادات عند إعادة الموافقة على اختبار منشور سابقًا.

الجداول التي تزيد:

- <code>user_yearly_test_stats.published_tests_count</code>
- <code>user_yearly_test_publish_month_stats.published_tests_count</code>
- <code>user_profile_stats.published_tests_count</code>
- <code>admin_yearly_test_activity_month_stats.published_tests_count</code>

---

## API حذف اختبار

### الحالات العامة

- لا يمكن حذف اختبار محذوف مسبقًا.
- لا يمكن حذف اختبار خاص من لوحة التحكم.
- الحذف قد يكون soft delete أو force delete حسب الشراء.

### متى يكون الحذف Soft Delete؟

إذا كان الاختبار:

```text
عام + مدفوع + تم شراؤه من مستخدمين
```

يتم حذفه soft delete حتى يبقى متاحًا منطقيًا لمن اشتراه، ويمكن عرضه في عمود تم حذفه في لوحة الإدارة.

### متى يكون الحذف Force Delete؟

إذا كان الاختبار:

- مجانيًا.
- أو مدفوعًا لكن لم يشتره أحد.

يتم حذفه نهائيًا، ولا يظهر في عمود تم حذفه لأن بياناته لم تعد موجودة.

### التعامل مع test_review_rounds عند الحذف

| حالة الاختبار عند الحذف | ماذا نفعل مع الجولة؟ |
|---|---|
| <code>مسودة</code> | نغلق آخر جولة pending بقرار deleted إن وجدت. |
| <code>قيد المراجعة</code> | نغلق آخر جولة pending بقرار deleted. |
| <code>مبلغ عنه</code> | نغلق جولة auto_report المفتوحة بقرار deleted. |
| <code>يحتاج تعديل</code> | غالبًا لا توجد جولة pending، لا نعدّل الجولة القديمة المغلقة. |
| <code>تم الموافقة عليه</code> | غالبًا لا توجد جولة pending، لا نعدّل جولة الموافقة القديمة. |
| <code>تم حذفه</code> | نمنع العملية. |

<div class="warning">
لا نعدل جولة مراجعة مغلقة سابقًا، لأن ذلك يفسد التاريخ الإداري. إذا كانت الجولة مغلقة بقرار approved أو needs_revision تبقى كما هي.
</div>

### التعامل مع test عند Soft Delete

نحدث الحالة أولًا:

```text
review_status = تم حذفه
```

ثم ننشئ status history، ثم ننفذ:

```text
test.delete()
```

### التعامل مع test_status_histories عند Soft Delete

ننشئ سجلًا:

```text
test_id = test.id
test_review_round_id = round.id أو null إذا لم توجد جولة pending
from_status = الحالة السابقة
to_status = تم حذفه
changed_by_user_id = reviewer.id
note = سبب الحذف
```

### التعامل مع Force Delete

عند force delete لا نحتاج إنشاء status history لأن السجلات التابعة في:

- <code>test_review_rounds</code>
- <code>test_status_histories</code>
- <code>test_revision_requests</code>

ستُحذف عبر cascade.

يتم الاعتماد على audit log و broadcast event لتحديث الواجهة.

### تحديث Summary Tables عند الحذف

ننقص عدادات النشر فقط إذا كان الاختبار منشورًا سابقًا:

```text
current_approval_version > 0
```

ويكون النقص من سنة وشهر <code>published_at</code> وليس من شهر الحذف، لأن العداد يمثل شهر النشر.

الجداول التي تنقص:

- <code>user_yearly_test_stats.published_tests_count</code>
- <code>user_yearly_test_publish_month_stats.published_tests_count</code>
- <code>user_profile_stats.published_tests_count</code>
- <code>admin_yearly_test_activity_month_stats.published_tests_count</code>

---

## API طلب تعديلات على اختبار

### الحالات المسموحة

يمكن طلب تعديلات إذا كانت حالة الاختبار:

- <code>مسودة</code>
- <code>مبلغ عنه</code>
- <code>يحتاج تعديل</code> ضمن شروط خاصة

### الحالات الممنوعة

| الحالة | السبب |
|---|---|
| <code>تم الموافقة عليه</code> | لا يمكن طلب تعديلات على اختبار منشور مباشرة. يجب أن يدخل دورة بلاغات أولًا. |
| <code>قيد المراجعة</code> | هذه الحالة تعني أن المستخدم عدّل وأعاد الإرسال، والمشرف أمامه موافقة أو حذف فقط. |
| <code>تم حذفه</code> | لا يمكن تعديل اختبار محذوف. |
| اختبار خاص | لا يدخل دورة مراجعة لوحة التحكم. |

### أنواع التعديلات المدعومة

| revision_type | المطلوب من الفرونت | أين يتم التخزين؟ |
|---|---|---|
| <code>question_text</code> | رقم السؤال + وصف المشكلة | <code>target_question_id</code> |
| <code>answer_text</code> | رقم السؤال + رقم الإجابة + وصف المشكلة | <code>target_question_id</code> و <code>target_option_id</code> |
| <code>hint_text</code> | رقم السؤال + وصف المشكلة | <code>target_question_id</code> |
| <code>description</code> | وصف المشكلة فقط | بدون target |
| <code>correct_answer</code> | رقم السؤال + وصف المشكلة | <code>target_question_id</code> |

### الحد الأقصى للتعديلات

- يجب إرسال تعديل واحد على الأقل.
- يمكن إرسال 8 تعديلات كحد أقصى.
- إذا كانت الجولة تحتوي تعديلات سابقة، يكون الشرط:

```text
existing_revision_requests_count + new_revision_requests_count <= 8
```

### من يستطيع إضافة تعديلات لاحقة؟

إذا كان الاختبار حالته <code>يحتاج تعديل</code>، فهذا يعني أن هناك جولة مغلقة بقرار <code>needs_revision</code>.

في هذه الحالة:

- لا نغيّر حالة الاختبار.
- لا نغلق جولة جديدة.
- لا ننشئ status history جديد.
- نضيف requests جديدة على نفس الجولة.
- فقط المشرف الذي طلب التعديلات أول مرة يستطيع إضافة تعديلات أخرى لنفس الجولة.

### التعامل مع test_review_rounds عند طلب التعديلات

| حالة الاختبار | التعامل مع الجولة |
|---|---|
| <code>مسودة</code> | نغلق آخر جولة pending بقرار needs_revision. |
| <code>مبلغ عنه</code> | نغلق آخر جولة pending غالبًا auto_report بقرار needs_revision. |
| <code>يحتاج تعديل</code> | نستخدم آخر جولة decision = needs_revision ولا ننشئ أو نغلق جولة جديدة. |
| <code>قيد المراجعة</code> | نمنع العملية. |
| <code>تم الموافقة عليه</code> | نمنع العملية. |
| <code>تم حذفه</code> | نمنع العملية. |

### التعامل مع test

إذا كانت الحالة <code>مسودة</code> أو <code>مبلغ عنه</code>:

```text
review_status = يحتاج تعديل
```

إذا كانت الحالة أصلًا <code>يحتاج تعديل</code>:

```text
لا تغيير على test.review_status
```

### التعامل مع test_status_histories

ننشيء status history فقط إذا تغيرت الحالة إلى <code>يحتاج تعديل</code>.

مثال:

```text
from_status = مسودة
to_status = يحتاج تعديل
```

أو:

```text
from_status = مبلغ عنه
to_status = يحتاج تعديل
```

أما في حالة إضافة ملاحظات إضافية والاختبار أصلًا <code>يحتاج تعديل</code>:

```text
لا ننشئ status history جديد
```

لأن الحالة لم تتغير.

### التعامل مع test_revision_requests

ننشئ صفًا لكل تعديل مطلوب:

```text
test_review_round_id = round.id
test_id = test.id
revision_type = نوع التعديل
target_question_id = السؤال المستهدف أو null
target_option_id = الخيار المستهدف أو null
created_by_user_id = reviewer.id
problem_note = وصف المشكلة
resolved_at = null
```

### التعامل مع test_revision_change_logs

لا يتم التعامل معه في هذه API.

هذا الجدول يخص تعديلات المستخدم الفعلية لاحقًا، مثل:

```text
before_value = النص القديم
after_value = النص الجديد
changed_by_user_id = صاحب الاختبار
```

---

## قواعد التعامل مع current_approval_version و based_on_approval_version

### current_approval_version في test

| القيمة | المعنى |
|---|---|
| <code>0</code> | الاختبار لم يُنشر للعامة أبدًا. |
| <code>1</code> | تم نشر أول نسخة للعامة. |
| <code>2</code> | تم نشر نسخة ثانية بعد تعديلات لاحقة. |
| <code>n</code> | رقم آخر نسخة منشورة. |

### متى يزيد current_approval_version؟

يزيد عند الموافقة على اختبار فيه تغيير محتوى أو أول نشر.

أمثلة:

| الحالة | هل يزيد؟ |
|---|---:|
| <code>مسودة -> تم الموافقة عليه</code> | نعم |
| <code>قيد المراجعة -> تم الموافقة عليه</code> | نعم |
| <code>مبلغ عنه -> تم الموافقة عليه</code> بسبب بلاغ غير صحيح | لا |

### based_on_approval_version في test_review_rounds

هذا الحقل يحدد النسخة المنشورة التي بُنيت عليها الجولة.

أمثلة:

#### أول إرسال قبل أي نشر

```text
current_approval_version = 0
trigger_type = initial_submission
based_on_approval_version = 0
```

#### إعادة إرسال قبل أول نشر

```text
current_approval_version = 0
trigger_type = owner_resubmission
based_on_approval_version = 0
```

#### بلاغات على النسخة 1

```text
current_approval_version = 1
trigger_type = auto_report
based_on_approval_version = 1
```

#### إعادة إرسال بعد تعديلات بسبب بلاغات النسخة 1

```text
trigger_type = owner_resubmission
based_on_approval_version = 1
```

وعند الموافقة بعد ذلك:

```text
current_approval_version = 2
```

---

## قواعد التزامن Race Conditions

كل APIs القرار تستخدم نفس نمط الحماية:

1. بدء transaction.
2. قفل صف الاختبار بواسطة <code>lockForUpdate()</code>.
3. التحقق من الحالة الحالية.
4. قفل جولة المراجعة المفتوحة عند الحاجة.
5. تنفيذ القرار.
6. إنشاء status history عند تغير الحالة.
7. إطلاق events بعد نجاح transaction.

<div class="success">
بهذا إذا حاول مشرفان اتخاذ قرارين على نفس الاختبار، سينجح الأول، والثاني سيقرأ الحالة الجديدة أو يجد الاختبار محذوفًا، ويرجع له خطأ مناسب مثل 409 Conflict.
</div>

كما يمكن استخدام <code>Idempotency-Key</code> على Routes القرار لحماية نفس الطلب من التكرار بسبب double click أو retry.

---

## تحديث الواجهة عبر Laravel Reverb

بعد كل قرار إداري، يتم إطلاق Broadcast Event على قناة خاصة مثل:

```text
private-dashboard.test-management
```

### عند الموافقة

يرسل الحدث:

```json
{
  "test_id": 15,
  "from_status": "مسودة",
  "to_status": "تم الموافقة عليه",
  "changed_date": "2026-06-18",
  "changed_at": "2026-06-18 12:30:00",
  "current_approval_version": 1
}
```

### عند الحذف

يرسل الحدث أيضًا:

```json
{
  "test_id": 15,
  "from_status": "تم الموافقة عليه",
  "to_status": "تم حذفه",
  "deletion_type": "soft_delete",
  "should_appear_in_deleted_column": true
}
```

إذا كان <code>force_delete</code>، لا يجب إضافة الاختبار لعمود تم حذفه.

### عند طلب تعديلات

إذا تغيرت الحالة:

```json
{
  "test_id": 15,
  "from_status": "مسودة",
  "to_status": "يحتاج تعديل",
  "status_changed": true
}
```

إذا كانت الحالة أصلًا <code>يحتاج تعديل</code> وتمت إضافة طلبات جديدة:

```json
{
  "test_id": 15,
  "from_status": "يحتاج تعديل",
  "to_status": "يحتاج تعديل",
  "status_changed": false
}
```

---

## تحديث جداول Summary

### عند الموافقة

نزيد العدادات فقط إذا:

```text
old current_approval_version = 0
```

أي أن الاختبار ظهر للعامة لأول مرة.

### عند إعادة الموافقة

لا نزيد العدادات إذا كان الاختبار منشورًا سابقًا.

مثال:

```text
current_approval_version = 1
reported -> needs_revision -> under_review -> approved
```

هنا يصبح:

```text
current_approval_version = 2
```

لكن لا تزيد عدادات عدد الاختبارات المنشورة، لأنه نفس الاختبار وليس اختبارًا جديدًا.

### عند الحذف

ننقص العدادات فقط إذا:

```text
current_approval_version > 0
```

وننقص من سنة وشهر <code>published_at</code>.

### الجداول المتأثرة

| الجدول | الحقل |
|---|---|
| <code>user_yearly_test_stats</code> | <code>published_tests_count</code> |
| <code>user_yearly_test_publish_month_stats</code> | <code>published_tests_count</code> |
| <code>user_profile_stats</code> | <code>published_tests_count</code> |
| <code>admin_yearly_test_activity_month_stats</code> | <code>published_tests_count</code> |

---

## خلاصة الحالات

| الحالة الحالية | موافقة | حذف | طلب تعديلات |
|---|---|---|---|
| <code>مسودة</code> | مسموح | مسموح | مسموح |
| <code>يحتاج تعديل</code> | ممنوع | مسموح | مسموح فقط لنفس المشرف وبحد 8 طلبات للجولة |
| <code>قيد المراجعة</code> | مسموح | مسموح | ممنوع |
| <code>تم الموافقة عليه</code> | ممنوع | مسموح | ممنوع |
| <code>مبلغ عنه</code> | مسموح، ولا ترفع النسخة | مسموح | مسموح |
| <code>تم حذفه</code> | ممنوع | ممنوع | ممنوع |

---

## أمثلة Workflow كاملة

### مثال 1: اختبار جديد تمت الموافقة عليه مباشرة

```text
test.review_status = مسودة
test.current_approval_version = 0
round.decision = pending
```

بعد الموافقة:

```text
round.decision = approved
round.reviewer_user_id = supervisor.id
round.decided_at = now()

test.review_status = تم الموافقة عليه
test.current_approval_version = 1
test.published_at = now()

status_history:
from_status = مسودة
to_status = تم الموافقة عليه
```

وتزيد summary counters لأن هذه أول مرة يظهر فيها الاختبار للعامة.

### مثال 2: اختبار جديد احتاج تعديل

```text
round.decision = pending
```

بعد طلب التعديل:

```text
round.decision = needs_revision
round.reviewer_user_id = supervisor.id
round.decided_at = now()

test.review_status = يحتاج تعديل

status_history:
from_status = مسودة
to_status = يحتاج تعديل
```

وتُنشأ صفوف في <code>test_revision_requests</code> فقط.

### مثال 3: إضافة تعديلات أخرى لنفس الجولة

إذا كان:

```text
test.review_status = يحتاج تعديل
latest round.decision = needs_revision
latest round.reviewer_user_id = supervisor.id
```

يمكن لنفس المشرف إضافة طلبات جديدة بشرط مجموع الطلبات <= 8.

لا نغير:

```text
test.review_status
round.decision
test_status_histories
```

### مثال 4: اختبار منشور تم الإبلاغ عنه ثم البلاغ كان غير صحيح

```text
test.review_status = مبلغ عنه
test.current_approval_version = 1
round.trigger_type = auto_report
round.based_on_approval_version = 1
round.decision = pending
```

بعد الموافقة:

```text
round.decision = approved

test.review_status = تم الموافقة عليه
test.current_approval_version = 1
```

لا تزيد النسخة ولا summary counters.

### مثال 5: اختبار منشور تم الإبلاغ عنه واحتاج تعديل

```text
test.review_status = مبلغ عنه
test.current_approval_version = 1
round.trigger_type = auto_report
round.based_on_approval_version = 1
```

بعد طلب التعديل:

```text
round.decision = needs_revision

test.review_status = يحتاج تعديل

status_history:
from_status = مبلغ عنه
to_status = يحتاج تعديل
```

ثم يعدّل المستخدم ويعيد الإرسال، فيُنشأ round جديد:

```text
trigger_type = owner_resubmission
based_on_approval_version = 1
decision = pending
```

إذا تمت الموافقة لاحقًا:

```text
test.current_approval_version = 2
```

لكن لا تزيد summary counters لأنه نفس الاختبار نُشر سابقًا.

### مثال 6: حذف اختبار منشور مدفوع ومُشترى

يتم soft delete:

```text
test.review_status = تم حذفه
test.deleted_at = now()
```

وينشأ status history:

```text
from_status = تم الموافقة عليه
to_status = تم حذفه
note = سبب الحذف
```

وتنقص summary counters من شهر وسنة <code>published_at</code>.

### مثال 7: حذف اختبار مجاني أو غير مشترى

يتم force delete.

بسبب cascade، تُحذف سجلات الجولات والتاريخ التابعة، ولا يظهر في عمود تم حذفه.
