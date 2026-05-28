<style>
  :root {
    color-scheme: dark;
    --bg: #0f1117;
    --panel: #151923;
    --panel-2: #1b2030;
    --text: #e8edf7;
    --muted: #a8b2c7;
    --line: #2b3347;
    --accent: #58a6ff;
    --accent-2: #7ee787;
    --warn: #f2cc60;
    --danger: #ff7b72;
    --code: #0b0e14;
  }

  body {
    direction: rtl;
    text-align: right;
    background: var(--bg);
    color: var(--text);
    font-family: "Cairo", "Tajawal", "Segoe UI", Tahoma, Arial, sans-serif;
    line-height: 1.85;
  }

  h1, h2, h3 {
    color: var(--text);
    letter-spacing: 0;
  }

  h1 {
    padding: 24px;
    border: 1px solid var(--line);
    background: linear-gradient(135deg, #151923 0%, #1f2937 100%);
    border-radius: 12px;
  }

  h2 {
    margin-top: 42px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--line);
  }

  h3 {
    margin-top: 28px;
    color: var(--accent-2);
  }

  a {
    color: var(--accent);
  }

  .doc-card {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 18px 20px;
    margin: 16px 0;
  }

  .note {
    border-inline-start: 4px solid var(--accent);
  }

  .warning {
    border-inline-start: 4px solid var(--warn);
  }

  .danger {
    border-inline-start: 4px solid var(--danger);
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin: 18px 0;
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 10px;
    overflow: hidden;
  }

  th, td {
    border: 1px solid var(--line);
    padding: 10px 12px;
    vertical-align: top;
  }

  th {
    background: var(--panel-2);
    color: var(--accent-2);
  }

  code {
    direction: ltr;
    unicode-bidi: embed;
    color: #d2e8ff;
    background: var(--code);
    border: 1px solid var(--line);
    border-radius: 6px;
    padding: 2px 6px;
    font-family: "JetBrains Mono", "Fira Code", Consolas, monospace;
  }

  pre {
    direction: ltr;
    text-align: left;
    background: var(--code);
    color: #d2e8ff;
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 16px;
    overflow-x: auto;
  }

  pre code {
    border: 0;
    padding: 0;
    background: transparent;
  }

  .toc ol {
    margin: 0;
  }

  .badge {
    display: inline-block;
    background: #1f6feb22;
    color: var(--accent);
    border: 1px solid #1f6feb66;
    border-radius: 999px;
    padding: 2px 10px;
    margin: 2px;
    font-size: 0.9em;
  }
</style>

# توثيق ميزة توليد الأسئلة بالذكاء الاصطناعي

<div class="doc-card note">
هذا الملف يشرح مسار API التالي داخل مشروع Laravel:
<br>
<code>POST /ai-question-generations</code>
<br>
الشرح يغطي مسار التنفيذ البرمجي، منطق العمل، حالات الاختبار، اختيار سلسلة المزودين، وآلية قراءة الصور والملفات عند استخدام موديلات نصية فقط.
</div>

## فهرس المحتويات

<div class="doc-card toc">

1. [نظرة عامة سريعة](#نظرة-عامة-سريعة)
2. [Flow التنفيذ البرمجي للـ API](#flow-التنفيذ-البرمجي-للـ-api)
3. [Flow منطق العمل Business Logic](#flow-منطق-العمل-business-logic)
4. [جدول حالات الاختبار](#جدول-حالات-الاختبار)
5. [شرح الكلاسات والتوابع](#شرح-الكلاسات-والتوابع)
6. [طريقة اختيار السلسلة وحساب النقاط](#طريقة-اختيار-السلسلة-وحساب-النقاط)
7. [قراءة محتوى الصور والملفات للموديلات النصية](#قراءة-محتوى-الصور-والملفات-للموديلات-النصية)
8. [ملاحظات تشغيل واختبار](#ملاحظات-تشغيل-واختبار)

</div>

## نظرة عامة سريعة

الميزة تستقبل صوراً أو ملف PDF من المستخدم، ثم تنشئ طلب توليد أسئلة، وتخزن الملفات مؤقتاً، وبعدها تشغل Job في الخلفية. الـ Job يختار سلسلة Providers مناسبة حسب نوع المصدر، الصعوبة، عدد الأسئلة، وعدد/حجم الملفات. كل Provider يحاول توليد أسئلة MCQ بصيغة JSON، وإذا فشل مزود بشكل قابل للتجاوز ينتقل النظام إلى المزود التالي.

<div class="doc-card">

**المدخلات الأساسية:**

- <code>source_type</code>: إما <code>Images</code> أو <code>Pdf</code>.
- <code>question_count</code>: عدد الأسئلة المطلوب.
- <code>difficulty_level</code>: <code>Easy</code> أو <code>Medium</code> أو <code>Hard</code>.
- <code>language</code>: حالياً من الـ Request: <code>English</code> أو <code>Arabic</code>.
- <code>files[]</code>: صور أو ملف PDF.

**النواتج الأساسية:**

- في البداية: <code>generation_request_id</code> و <code>status</code>.
- عند الاستعلام لاحقاً: حالة الطلب والأسئلة أو سبب الفشل.

</div>

## Flow التنفيذ البرمجي للـ API

### 1. نقطة الدخول Route

```php
Route::post('/ai-question-generations', [AiQuestionGenerationController::class, 'store']);
```

المسار موجود داخل <code>routes/api.php</code>، ويصل إلى:

```php
App\Http\Controllers\V1\AiQuestionGeneration\AiQuestionGenerationController@store
```

### 2. التسلسل الكامل من البداية إلى النهاية

```text
POST /ai-question-generations
    |
    v
AiQuestionGenerationController::store()
    |
    v
StoreAiQuestionGenerationRequest
    |-- rules()
    |-- withValidator()
    |   |-- validateImages()
    |   '-- validatePdf()
    |
    v
AiQuestionGenerationService::create()
    |-- AiQuestionGenerationLocalFileValidationService::validate()
    |   |-- ImageContentHeuristicValidator::validate()
    |   '-- PdfStructureValidator::validate()
    |
    |-- AiQuestionGenerationReuseService::buildSignature()
    |-- AiQuestionGenerationReuseService::findReusableRequest()
    |
    |-- assertUserWithinDailyLimit()
    |
    |-- DB transaction
    |   '-- AiQuestionGenerationRepository::createRequest()
    |
    |-- AiQuestionGenerationFileStorageService::storeUploadedFiles()
    |   '-- AiQuestionGenerationRepository::createAsset()
    |
    |-- AiQuestionGenerationReuseService::rememberRequest()
    |
    '-- dispatch ProcessAiQuestionGenerationJob
          |
          v
ProcessAiQuestionGenerationJob::handle()
    |-- AiQuestionGenerationRepository::findWithAssetsById()
    |-- AiQuestionGenerationRepository::markAsProcessing()
    |
    |-- AiQuestionGenerationProviderOrchestrator::generate()
    |   |-- AiQuestionGenerationRoutingPolicy::buildProviderChain()
    |   |-- AiProviderCapabilityService::filterProviderChain()
    |   |-- AiQuestionGenerationProviderManager::namedChain()
    |   |
    |   |-- foreach provider:
    |   |   |-- AiProviderHealthService::isAvailable()
    |   |   |-- Provider::generate()
    |   |   |-- AiGeneratedQuestionNormalizer::normalize()
    |   |   '-- عند الفشل:
    |   |       |-- AiProviderFailureClassifier::classify()
    |   |       |-- AiProviderHealthService::markUnavailable()
    |   |       '-- تجربة المزود التالي إن كان الفشل retryable
    |   |
    |   '-- return provider/model/questions
    |
    |-- AiQuestionGenerationRepository::markAsCompleted()
    |-- AiQuestionGenerationFileStorageService::deleteStoredAssets()
    |
    '-- عند الفشل:
        '-- AiQuestionGenerationRepository::markAsFailed()
```

### 3. Flow مختصر ككود منطقي

```php
public function store(StoreAiQuestionGenerationRequest $request): JsonResponse
{
    $data = $service->create(
        user: $request->user(),
        data: $request->validated(),
        files: $request->file('files', [])
    );

    return response()->json($data, 202);
}
```

```php
public function create(User $user, array $data, array $files): array
{
    validateLocalFiles();
    $signature = buildDuplicateSignature();

    if ($existing = findReusableRequest()) {
        return reusedResponse($existing);
    }

    assertDailyLimit();
    $request = createGenerationRequest();
    storeUploadedFiles($request);
    rememberDuplicateFingerprint();
    dispatchJob($request->id);

    return acceptedResponse($request);
}
```

```php
public function handle(): void
{
    $request = findRequestWithAssets();
    markAsProcessing($request);

    try {
        $result = orchestrator->generate($request);
        markAsCompleted($request, $result);
        deleteTemporaryAssets($request);
    } catch (Throwable $e) {
        markAsFailed($request, $e);
        throw $e;
    }
}
```

## Flow منطق العمل Business Logic

### الفكرة العامة

منطق العمل مبني على مبدأ مهم: طلب المستخدم لا ينتظر الذكاء الاصطناعي داخل HTTP request. الـ API يستقبل الملفات، يتحقق منها، ينشئ سجل طلب، ثم يطلق Job في الخلفية. هذا يجعل endpoint سريعاً وقابلاً للتوسع، ويمنع timeout عند التعامل مع ملفات كبيرة أو مزودين بطيئين.

<div class="doc-card note">
بمعنى آخر: <code>POST /ai-question-generations</code> لا يعني "ولّد الأسئلة الآن وانتظر"، بل يعني "استقبل الطلب، جهزه، وشغّل عملية التوليد في الخلفية".
</div>

### 1. استقبال الطلب من المستخدم

المستخدم يرسل:

- نوع المصدر: <code>Images</code> أو <code>Pdf</code>.
- عدد الأسئلة المطلوب.
- مستوى الصعوبة.
- لغة الأسئلة.
- الملفات.

النظام يتعامل مع الطلب كمهمة غير متزامنة. لذلك إذا تم قبول الطلب بنجاح، يرجع:

```text
HTTP 202 Accepted
```

مع:

```json
{
  "generation_request_id": 123,
  "status": "Pending",
  "reused": false
}
```

### 2. التحقق الأولي من شكل الطلب

أول طبقة تحقق هي <code>StoreAiQuestionGenerationRequest</code>. هذه الطبقة لا تحاول فهم المحتوى التعليمي بعمق، لكنها تتأكد أن شكل الطلب صحيح.

| نقطة التحقق | الهدف |
|---|---|
| <code>source_type</code> | يجب أن يكون <code>Images</code> أو <code>Pdf</code>. |
| <code>question_count</code> | يجب أن يكون ضمن الحد الأدنى والأعلى في config. |
| <code>difficulty_level</code> | يجب أن يكون <code>Easy</code> أو <code>Medium</code> أو <code>Hard</code>. |
| <code>language</code> | يجب أن تكون لغة مدعومة. |
| <code>files</code> | يجب وجود ملفات، وأن تكون من الأنواع المسموحة. |

ثم تأتي قواعد مخصصة حسب نوع المصدر:

- إذا كان <code>Images</code>: يجب أن يكون عدد الصور بين الحد الأدنى والأعلى، وكل الملفات صور.
- إذا كان <code>Pdf</code>: يجب رفع ملف PDF واحد فقط.

### 3. التحقق المحلي من جودة الملفات

بعد نجاح validation الأولي، ينتقل الطلب إلى <code>AiQuestionGenerationService::create()</code>، وهناك يتم تشغيل:

```php
AiQuestionGenerationLocalFileValidationService::validate()
```

هذه الطبقة تفحص الملف من زاوية عملية أكثر:

| نوع المصدر | الفحص المحلي |
|---|---|
| Images | فحص أبعاد الصورة، التأكد أنها قابلة للقراءة، وألا تكون فارغة أو موحدة اللون تقريباً. |
| Pdf | فحص بنية PDF عبر <code>pdfinfo</code> والتأكد من وجود صفحات. |

الغرض من هذه الخطوة هو منع الملفات السيئة مبكراً قبل تخزينها وتشغيل Job واستهلاك providers.

### 4. بناء بصمة الطلب ومنع التكرار

قبل إنشاء طلب جديد، يبني النظام signature/fingerprint من:

- <code>user_id</code>
- <code>source_type</code>
- <code>question_count</code>
- <code>difficulty_level</code>
- <code>language</code>
- hashes الملفات عبر <code>sha256</code>

ثم يبحث عن طلب سابق مطابق.

إذا وجد طلباً مطابقاً وحالته:

- <code>Pending</code>
- <code>Processing</code>
- <code>Completed</code>

فالنظام لا ينشئ طلباً جديداً، بل يعيد نفس <code>generation_request_id</code> مع:

```json
{
  "reused": true
}
```

<div class="doc-card note">
هذه الخطوة تقلل التكلفة وتمنع المستخدم من استهلاك الحد اليومي أو مزودي الذكاء الاصطناعي لنفس الملفات ونفس الإعدادات.
</div>

### 5. التحقق من الحد اليومي

إذا لم يوجد طلب قابل لإعادة الاستخدام، يفحص النظام الحد اليومي للمستخدم.

الحد يعتمد على حالة المستخدم:

| نوع المستخدم | الحد المستخدم |
|---|---|
| موثق أكاديمياً | <code>verified_user_daily_limit</code> |
| غير موثق أكاديمياً | <code>unverified_user_daily_limit</code> |

ويحسب فقط الطلبات النشطة في نفس اليوم:

- <code>Pending</code>
- <code>Processing</code>
- <code>Completed</code>

أما الطلبات <code>Failed</code> فلا تحتسب ضمن الحد اليومي النشط.

### 6. إنشاء سجل الطلب داخل قاعدة البيانات

بعد اجتياز كل الشروط السابقة، ينشئ النظام سجل في جدول:

```text
ai_question_generation_requests
```

القيم الأساسية:

| الحقل | القيمة |
|---|---|
| <code>user_id</code> | صاحب الطلب |
| <code>source_type</code> | Images أو Pdf |
| <code>status</code> | يبدأ دائماً بـ <code>Pending</code> |
| <code>requested_question_count</code> | العدد المطلوب |
| <code>difficulty_level</code> | الصعوبة |
| <code>language</code> | اللغة |

### 7. تخزين الملفات وربطها بالطلب

بعد إنشاء الطلب، يخزن النظام الملفات في storage مؤقت داخل:

```text
ai-question-generations/{generation_request_id}
```

ويتم إنشاء سجل لكل ملف داخل:

```text
ai_question_generation_assets
```

كل asset يحفظ:

- disk المستخدم.
- path داخل storage.
- الاسم الأصلي.
- MIME type.
- الحجم.
- SHA256 hash.
- position.

### 8. حفظ fingerprint في cache

بعد نجاح التخزين، يحفظ النظام fingerprint في cache لمدة:

```php
duplicate_cache_ttl_days
```

هذا يجعل اكتشاف الطلبات المتكررة أسرع لاحقاً، قبل الرجوع للبحث الأوسع في قاعدة البيانات.

### 9. تشغيل Job في الخلفية

بعد تجهيز الطلب والملفات، يتم dispatch لـ:

```php
ProcessAiQuestionGenerationJob
```

على queue محدد في:

```php
config('ai_question_generation.queue_name')
```

في هذه اللحظة ينتهي عمل endpoint الأساسي، ويرجع للمستخدم response سريع. أما التوليد نفسه فيحدث داخل queue worker.

### 10. انتقال الطلب إلى Processing

عندما يبدأ الـ Job:

1. يجلب الطلب مع الملفات.
2. يتأكد أن الحالة ما زالت <code>Pending</code>.
3. يغير الحالة إلى <code>Processing</code>.
4. يسجل <code>started_at</code>.

إذا كانت الحالة ليست <code>Pending</code>، يتم تجاهل الـ Job لتفادي التكرار أو التشغيل المزدوج.

### 11. اختيار سلسلة المزودين

داخل الـ Job، يبدأ:

```php
AiQuestionGenerationProviderOrchestrator::generate()
```

الـ Orchestrator لا يختار provider واحداً فقط، بل يبني سلسلة providers. ثم يجربهم بالترتيب.

إذا فشل مزود بسبب سبب قابل للتجاوز مثل:

- rate limit
- temporary unavailable
- connection failed
- invalid response
- unsupported source type
- text extraction failed

ينتقل النظام إلى المزود التالي.

أما إذا كان الفشل نهائياً أو لا يوجد مزود آخر، ينتهي الطلب كفشل.

### 12. تشغيل المزود المناسب

كل provider يطبق نفس interface:

```php
generate(AiQuestionGenerationRequest $generationRequest): array
```

لكن طريقة قراءة الملفات تختلف:

| Provider | طريقة التعامل مع الملفات |
|---|---|
| Gemini | يرسل الصور أو PDF raw إلى Gemini. |
| OpenRouter | يرسل الصور raw كـ data URL. |
| Cloudflare Workers AI | صورة واحدة raw image، أو PDF/صور متعددة عبر toMarkdown. |
| DeepSeek | يستخرج النص أولاً ثم يرسله كنص. |
| Ollama Cloud | يستخرج النص أولاً ثم يرسله كنص. |
| Hugging Face | يستخرج النص أولاً ثم يرسله كنص. |
| Ollama Local | يرسل الصور raw محلياً، وهو fallback أخير للصور. |

### 13. توحيد نتيجة الذكاء الاصطناعي

بعد أن يرجع provider نتيجة، تمر عبر:

```php
AiGeneratedQuestionNormalizer::normalize()
```

هذه الطبقة مهمة لأنها لا تثق بالـ AI بشكل كامل.

تتحقق من:

- أن <code>content_type</code> ليس <code>NotEducational</code>.
- وجود مصفوفة <code>questions</code>.
- أن عدد الأسئلة لا يقل عن 50% من العدد المطلوب.
- أن كل سؤال له نص.
- أن الخيارات بين 2 و 5.
- أن كل سؤال له إجابة صحيحة واحدة فقط.
- قص النصوص الطويلة إلى حدود آمنة.

### 14. حفظ النتيجة الناجحة

إذا نجح المزود:

- تحفظ الأسئلة في <code>generated_questions_json</code>.
- يحفظ اسم provider و model.
- تصبح الحالة <code>Completed</code>.
- تحذف الملفات المؤقتة من storage.

وتصبح النتيجة متاحة عند استدعاء:

```text
GET /ai-question-generations/{id}
```

### 15. مسار الفشل

إذا فشل كل المزودين أو حدث فشل نهائي:

- يغير النظام الحالة إلى <code>Failed</code>.
- يحفظ <code>failure_code</code>.
- يحفظ <code>failure_message</code>.
- يسجل <code>failed_at</code>.

### 16. ملخص دورة الحياة

```text
Pending
  |
  v
Processing
  |
  |-- success --> Completed
  |
  '-- failure --> Failed
```

### 17. ماذا يرى المستخدم مقابل ماذا يحدث داخلياً؟

من المهم التفريق بين تجربة المستخدم وبين التنفيذ الداخلي:

| المرحلة | ما يراه المستخدم | ما يحدث داخل النظام |
|---|---|---|
| إرسال الطلب | يحصل على <code>202 Accepted</code> ومعرّف الطلب | تم قبول الطلب فقط، ولم تنته عملية التوليد بعد. |
| الطلب في queue | يرى <code>status=Pending</code> | الملفات مخزنة والـ Job ينتظر worker. |
| بدء المعالجة | يرى <code>status=Processing</code> | النظام يختار provider chain ويبدأ المحاولات. |
| نجاح Provider | يرى <code>status=Completed</code> والأسئلة | تم normalize للأسئلة وحفظها وحذف الملفات المؤقتة. |
| فشل كل Providers | يرى <code>status=Failed</code> مع رسالة | تم حفظ كود الفشل وآخر سبب واضح يمكن عرضه أو تتبعه. |

هذا الفصل يجعل الـ API مناسباً للملفات والـ AI calls الطويلة، لأن المستخدم لا يبقى منتظراً استجابة HTTP حتى ينتهي المزود.

### 18. أين توجد قرارات Business Logic الأساسية؟

رغم أن الميزة موزعة على عدة classes، يمكن تلخيص أماكن القرار كالتالي:

| القرار | مكانه الأساسي |
|---|---|
| هل شكل request صحيح؟ | <code>StoreAiQuestionGenerationRequest</code> |
| هل الملف صالح محلياً؟ | <code>AiQuestionGenerationLocalFileValidationService</code> |
| هل الطلب مكرر؟ | <code>AiQuestionGenerationReuseService</code> |
| هل المستخدم تجاوز الحد اليومي؟ | <code>AiQuestionGenerationService</code> |
| ما السلسلة المناسبة؟ | <code>AiQuestionGenerationRoutingPolicy</code> |
| هل provider قادر على هذا الطلب؟ | <code>AiProviderCapabilityService</code> |
| هل فشل provider يسمح بالانتقال للتالي؟ | <code>AiProviderFailureClassifier</code> |
| هل نتيجة الـ AI صالحة؟ | <code>AiGeneratedQuestionNormalizer</code> |

بهذا الشكل يبقى الـ Controller خفيفاً، والـ Job مسؤولاً عن orchestration، بينما تفاصيل القرار موزعة على services متخصصة.

<div class="doc-card warning">
الملفات المؤقتة تحذف بعد النجاح فقط. عند الفشل، يمكن إبقاؤها مؤقتاً حسب السلوك الحالي لتسهيل التشخيص، أو تنظيفها لاحقاً بسياسة مستقلة إذا أردنا.
</div>

## جدول حالات الاختبار

| الحالة | المدخلات | السلوك المتوقع | النتيجة |
|---|---|---|---|
| صور صحيحة ومحتوى تعليمي | <code>source_type=Images</code> و 1-3 صور واضحة | التحقق المحلي ينجح، تنطلق Job، وتبدأ سلسلة الصور | <code>202 Accepted</code> ثم غالباً <code>Completed</code> |
| صورة فارغة أو لون واحد | صورة بيضاء/سوداء تقريباً | <code>ImageContentHeuristicValidator</code> يرفض الصورة | خطأ validation قبل إنشاء الطلب |
| صورة صغيرة جداً | أبعاد أقل من الحد الأدنى | رفض محلي | خطأ validation |
| أكثر من 3 صور | <code>files[]</code> بعدد أكبر من max | <code>StoreAiQuestionGenerationRequest</code> يرفض | خطأ validation |
| PDF صحيح | ملف PDF واحد | <code>PdfStructureValidator</code> يفحصه عبر <code>pdfinfo</code> ثم تنطلق Job | <code>202 Accepted</code> |
| PDF فارغ أو تالف | PDF غير قابل للقراءة | <code>PdfStructureValidator</code> يرفض | خطأ validation |
| رفع PDF مع source_type=Images | نوع مصدر لا يطابق الملفات | Request validator يرفض | خطأ validation |
| رفع صور مع source_type=Pdf | أكثر من ملف أو mime غير PDF | Request validator يرفض | خطأ validation |
| طلب مطابق سابقاً | نفس المستخدم ونفس الملفات ونفس الإعدادات | <code>ReuseService</code> يعيد الطلب السابق | response فيه <code>reused=true</code> |
| تجاوز الحد اليومي | عدد الطلبات النشطة اليوم وصل limit | <code>assertUserWithinDailyLimit</code> يرمي exception | <code>429</code> |
| مزود أول عليه rate limit | Provider يرجع 429 | يصنف كـ retryable ويجرب التالي | قد ينجح provider آخر |
| Provider لا يدعم نوع المصدر | مثل OpenRouter مع PDF | capability filter أو provider exception يمنعه | ينتقل التالي |
| model نصي فقط مع صورة | مثل DeepSeek | النظام يستخرج النص عبر OCR ثم يرسل النص | يمكن أن ينجح إذا OCR جيد |
| فشل OCR أو pdftotext | binary فشل أو النص قصير | exception <code>AI_ASSET_TEXT_EXTRACTION_FAILED</code> وتصنف retryable | ينتقل provider آخر |
| كل المزودين فشلوا | لا يوجد أي نتيجة صالحة | Job يعلّم الطلب Failed | <code>status=Failed</code> |
| AI يرجع JSON غير صالح | response لا يمكن decode | <code>providerInvalidResponse</code> | ينتقل التالي أو Failed |
| AI يقول NotEducational | <code>content_type=NotEducational</code> | <code>Normalizer</code> يرمي <code>contentIsNotEducational</code> | Failed أو خطأ منطقي حسب مسار Job |
| عدد الأسئلة أقل من 50% | عدد الأسئلة أقل من الحد المقبول | <code>Normalizer</code> يرفض | ينتقل التالي أو Failed |
| كل شيء نجح | Provider رجع JSON صالح | تحفظ الأسئلة وتحذف الملفات المؤقتة | <code>Completed</code> |

## شرح الكلاسات والتوابع

### Controller و Request

| الكلاس | المسؤولية | التوابع |
|---|---|---|
| <code>AiQuestionGenerationController</code> | نقطة دخول API للإنشاء والاستعلام | <code>store()</code>: يستقبل الطلب ويمرره للـ Service ويرجع 202. <br><code>show()</code>: يجلب حالة الطلب والأسئلة أو الفشل. |
| <code>StoreAiQuestionGenerationRequest</code> | validation للمدخلات | <code>rules()</code>: قواعد الحقول والملفات. <br><code>withValidator()</code>: validation إضافي حسب source_type. <br><code>validateImages()</code>: عدد الصور ونوعها وحجمها. <br><code>validatePdf()</code>: ملف PDF واحد فقط. <br><code>messages()</code>: رسائل الأخطاء العربية. |

### Service Layer

| الكلاس | المسؤولية | التوابع |
|---|---|---|
| <code>AiQuestionGenerationService</code> | تنسيق عملية إنشاء الطلب وتشغيل الـ Job | <code>create()</code>: validation، reuse، daily limit، إنشاء الطلب، تخزين الملفات، dispatch job. <br><code>show()</code>: عرض حالة الطلب. <br><code>assertUserWithinDailyLimit()</code>: فحص الحد اليومي. <br><code>formatQuestionsForResponse()</code>: تنسيق الأسئلة للرد. |
| <code>AiQuestionGenerationFileStorageService</code> | تخزين وحذف الملفات المؤقتة | <code>storeUploadedFiles()</code>: يخزن الملفات وينشئ assets. <br><code>deleteStoredAssets()</code>: يحذف الملفات بعد النجاح ويعلّم assets كمحذوفة. <br><code>deleteRequestDirectory()</code>: تنظيف عند فشل التخزين. <br><code>getRequestDirectory()</code>: بناء مسار مجلد الطلب. |
| <code>AiQuestionGenerationReuseService</code> | منع تكرار الطلبات المتطابقة | <code>buildSignature()</code>: يبني fingerprint من البيانات والملفات. <br><code>findReusableRequest()</code>: يبحث في cache ثم database. <br><code>rememberRequest()</code>: يخزن fingerprint في cache. <br><code>buildFileSignatures()</code>: يحسب hash/metadata للملفات. <br><code>requestAssetsMatchSignature()</code>: يقارن ملفات الطلب السابق بالجديد. |
| <code>AiGeneratedQuestionNormalizer</code> | توحيد وفحص JSON الناتج من AI | <code>normalize()</code>: يرفض NotEducational أو الأسئلة غير الكافية، ثم يقص العدد المطلوب. <br><code>normalizeQuestion()</code>: يفحص نص السؤال والخيارات والإجابة الصحيحة الواحدة. |

### Repository و Job

| الكلاس | المسؤولية | التوابع |
|---|---|---|
| <code>AiQuestionGenerationRepository</code> | التعامل مع قاعدة البيانات للطلبات والأصول | <code>countTodayActiveRequestsForUser()</code>. <br><code>createRequest()</code>. <br><code>createAsset()</code>. <br><code>findForUserWithAssets()</code>. <br><code>findWithAssetsById()</code>. <br><code>findReusableRequestById()</code>. <br><code>findReusableRequestBySignature()</code>. <br><code>markAsProcessing()</code>. <br><code>markAsCompleted()</code>. <br><code>markAsFailed()</code>. <br><code>markAssetAsDeletedFromStorage()</code>. |
| <code>ProcessAiQuestionGenerationJob</code> | تنفيذ التوليد في الخلفية | <code>handle()</code>: يجلب الطلب، يعلّمه Processing، يشغل Orchestrator، يحفظ النجاح أو الفشل. <br><code>failed()</code>: يضمن تعليم الطلب Failed إذا فشل Job نهائياً. |

### Provider Orchestration

| الكلاس | المسؤولية | التوابع |
|---|---|---|
| <code>AiQuestionGenerationProviderOrchestrator</code> | تجربة سلسلة المزودين حتى النجاح | <code>generate()</code>: يبني chain، يتخطى providers في cooldown، يجرب كل provider، يصنف الفشل، ويتحكم بالانتقال للمزود التالي. <br><code>hasNextAvailableProvider()</code>: يتأكد من وجود provider آخر متاح. |
| <code>AiQuestionGenerationProviderManager</code> | تحويل اسم provider إلى class | <code>default()</code>: يجلب provider الافتراضي. <br><code>provider()</code>: resolve provider من config ويتأكد من interface. <br><code>namedChain()</code>: يحول أسماء السلسلة إلى provider instances. |
| <code>AiQuestionGenerationRoutingPolicy</code> | اختيار chain حسب complexity و source_type | <code>buildProviderChain()</code>: يحسب النقاط ويختار chain. <br><code>configuredChainFor()</code>: يقرأ <code>chains_by_source_type</code> ثم fallback. <br><code>score()</code>: مجموع النقاط. <br><code>questionCountScore()</code>, <code>difficultyScore()</code>, <code>sourceTypeScore()</code>, <code>assetsCountScore()</code>, <code>assetsSizeScore()</code>. <br><code>levelForScore()</code>: يحول النقاط إلى low/medium/high. |
| <code>AiProviderCapabilityService</code> | فلترة providers حسب التسجيل والقدرات | <code>filterProviderChain()</code>: يزيل provider غير مسجل أو غير مناسب. <br><code>registeredProvidersSupporting()</code>: fallback حسب القدرات. <br><code>isRegistered()</code>. <br><code>supportsSourceType()</code>. <br><code>supportsAvailableInputMode()</code>. |
| <code>AiProviderFailureClassifier</code> | تحديد هل الفشل يسمح بتجربة provider آخر | <code>classify()</code>: يصنف exception. <br><code>classifyApiException()</code>: يقرأ failure_code. <br><code>isRetryableFailureCode()</code>: قائمة الأخطاء القابلة للتجاوز. <br><code>cooldownForFailureCode()</code>. |
| <code>AiProviderFailureDecision</code> | كائن قرار التصنيف | <code>retryable()</code>: قرار يسمح بتجربة التالي. <br><code>final()</code>: قرار يوقف السلسلة. |
| <code>AiProviderHealthService</code> | cooldown للمزودين المتعثرين | <code>isAvailable()</code>. <br><code>markUnavailable()</code>. <br><code>markAvailable()</code>. <br><code>unavailableReason()</code>. |

### Providers

| Provider | طريقة الإدخال | شرح |
|---|---|---|
| <code>GeminiQuestionGenerationProvider</code> | raw image / raw file | يرفع الملفات إلى Gemini أو يرسل الصور inline عند الحجم المناسب، ثم يستدعي <code>generateContent</code>. |
| <code>OpenRouterQuestionGenerationProvider</code> | raw image | يرسل الصور كـ <code>image_url</code> data URL إلى Chat Completions. يدعم الصور حالياً فقط حسب config. |
| <code>DeepSeekQuestionGenerationProvider</code> | extracted text | يستخدم <code>AiQuestionGenerationAssetTextExtractionService</code> لاستخراج النص، ثم يرسل prompt نصي فقط. |
| <code>OllamaLocalQuestionGenerationProvider</code> | raw image | يرسل base64 images إلى Ollama المحلي عبر <code>/api/chat</code>. يبقى آخر fallback للصور. |
| <code>OllamaCloudQuestionGenerationProvider</code> | extracted text | يستخدم Ollama Cloud API عبر <code>https://ollama.com/api/chat</code> ويرسل النص المستخرج. |
| <code>HuggingFaceInferenceQuestionGenerationProvider</code> | extracted text | يستخدم endpoint المتوافق مع OpenAI: <code>/chat/completions</code> ويرسل النص المستخرج. |
| <code>CloudflareWorkersAiQuestionGenerationProvider</code> | raw image / raw file toMarkdown | إذا كانت صورة واحدة يرسلها raw image إلى Llama Vision. إذا PDF أو عدة صور يحولها إلى Markdown عبر Cloudflare <code>toMarkdown</code> ثم يرسل النص للموديل. |

### Text Extraction

| الكلاس | المسؤولية | التوابع |
|---|---|---|
| <code>AiQuestionGenerationAssetTextExtractionService</code> | استخراج النص من assets وتجهيزه للـ prompt | <code>extractAssets()</code>: يستخرج كل assets. <br><code>extractPromptContext()</code>: يحول النتائج إلى نص واحد. <br><code>extractAsset()</code>: يختار PDF أو OCR. <br><code>prepareExtractedText()</code>: normalize + validate + limit. <br><code>normalizeExtractedText()</code>: تنظيف المسافات والأسطر. |
| <code>PdfTextExtractor</code> | استخراج نص PDF | <code>extract()</code>: يشغل <code>pdftotext</code> ويرجع النص. |
| <code>ImageOcrTextExtractor</code> | OCR للصور | <code>extract()</code>: يشغل <code>tesseract</code> ويرجع النص. |
| <code>ExtractedAssetText</code> | DTO للنص المستخرج | <code>formattedForPrompt()</code>: ينسق اسم الملف، MIME type، والنص. |

### Validation

| الكلاس | المسؤولية | التوابع |
|---|---|---|
| <code>AiQuestionGenerationLocalFileValidationService</code> | توجيه validation حسب نوع المصدر | <code>validate()</code>: يشغل فحص الصور أو PDF. |
| <code>ImageContentHeuristicValidator</code> | فحص جودة الصورة محلياً | <code>validate()</code>: يقرأ الصورة، يفحص الأبعاد، يصغر عينة، يحسب السطوع والتباين. <br><code>validateDimensions()</code>. <br><code>createImageResource()</code>. <br><code>createSampleImage()</code>. <br><code>calculateBrightnessStats()</code>. <br><code>isBlankOrUniform()</code>. |
| <code>PdfStructureValidator</code> | فحص بنية PDF | <code>validate()</code>: يشغل <code>pdfinfo</code> ويتأكد من وجود صفحات. |

### Models و Interface و Exceptions

| الكلاس | المسؤولية |
|---|---|
| <code>AiQuestionGenerationRequest</code> | Model يمثل طلب التوليد، يحتوي status واللغة والصعوبة والأسئلة الناتجة. |
| <code>AiQuestionGenerationAsset</code> | Model يمثل ملفاً مرفوعاً مرتبطاً بطلب التوليد. |
| <code>AiQuestionGenerationProviderInterface</code> | يفرض وجود <code>generate(AiQuestionGenerationRequest $generationRequest): array</code> لكل Provider. |
| <code>AiQuestionGenerationException</code> | Exceptions مخصصة للميزة: daily limit، file errors، provider errors، extraction errors، invalid generated questions. |

## طريقة اختيار السلسلة وحساب النقاط

### الهدف من نظام الاختيار

النظام لا يختار provider عشوائياً، ولا يعتمد فقط على provider الافتراضي. الفكرة هي بناء سلسلة مناسبة للطلب، بحيث يبدأ بالمزود الأكثر ملاءمة لنوع الملف وتعقيد الطلب، ثم ينتقل تدريجياً إلى بدائل أخرى عند الفشل.

النقاط لا تعني "اختر provider رقم كذا" مباشرة. النقاط فقط تحدد مستوى الطلب. بعد ذلك تأتي طبقات أخرى أكثر تحديداً:

- نوع المصدر: Images أو Pdf.
- قدرات provider المسجلة في config.
- أنماط الإدخال المتاحة: raw image، raw file، extracted text.
- صحة إعدادات provider ووجوده في قائمة providers.
- حالة provider الصحية إن كان عليه cooldown.

اختيار السلسلة يعتمد على ثلاث طبقات:

1. حساب تعقيد الطلب <code>complexity score</code>.
2. تحويل التعقيد إلى مستوى: <code>low</code> أو <code>medium</code> أو <code>high</code>.
3. اختيار chain حسب <code>source_type</code> والمستوى، ثم فلترتها حسب قدرات المزودين.

```text
Request metadata
    |
    v
score()
    |
    v
levelForScore()
    |
    v
chains_by_source_type[source_type][level]
    |
    v
AiProviderCapabilityService::filterProviderChain()
    |
    v
Final Provider Chain
```

### ترتيب القرار داخل Runtime

القرار النهائي يحدث بهذا التسلسل:

| الترتيب | القرار | النتيجة |
|---:|---|---|
| 1 | حساب <code>score</code> | رقم يمثل تعقيد الطلب. |
| 2 | تحويل score إلى <code>level</code> | low أو medium أو high. |
| 3 | قراءة سلسلة <code>chains_by_source_type</code> | سلسلة مخصصة للصور أو PDF. |
| 4 | fallback إلى <code>chains</code> عند الحاجة | يمنع توقف النظام إذا غاب config مخصص. |
| 5 | فلترة providers حسب القدرات | إزالة أي provider لا يناسب الطلب. |
| 6 | تجربة providers بالترتيب | أول نتيجة صالحة تنهي المعالجة بنجاح. |
| 7 | تصنيف الفشل | retryable ينتقل للتالي، non-retryable يوقف أو يفشل حسب الحالة. |

لذلك قد يكون الطلب <code>low</code>، ومع ذلك لا يستخدم أول provider مكتوب في config إذا كان لا يدعم نوع المصدر أو input mode المطلوب.

### 1. حساب النقاط Complexity Score

النظام يحسب complexity score من خمسة عوامل:

| العامل | القاعدة |
|---|---|
| عدد الأسئلة | أكثر من 10 يعطي نقطة، أكثر من 20 يعطي نقطتين، أكثر من 30 يعطي 3 نقاط. |
| الصعوبة | <code>Easy=0</code>, <code>Medium=1</code>, <code>Hard=3</code>. |
| نوع المصدر | <code>Images=1</code>, <code>Pdf=2</code>. |
| عدد الملفات | أكثر من 1 يعطي نقطة، أكثر من 2 يعطي نقطتين. |
| الحجم الإجمالي | أكثر من 1MB يعطي نقطة، أكثر من 4MB نقطتين، أكثر من 8MB ثلاث نقاط. |

الناتج النهائي هو مجموع هذه القيم:

```php
score =
    questionCountScore
  + difficultyScore
  + sourceTypeScore
  + assetsCountScore
  + assetsSizeScore;
```

### 1.1 نقاط عدد الأسئلة

| عدد الأسئلة | النقاط |
|---|---:|
| 5 إلى 10 | 0 |
| 11 إلى 20 | 1 |
| 21 إلى 30 | 2 |
| أكثر من 30 | 3 |

كلما زاد عدد الأسئلة زادت احتمالية التكرار أو انخفاض الجودة، لذلك يوجه النظام الطلبات الأكبر إلى مستوى أعلى.

### 1.2 نقاط الصعوبة

| الصعوبة | النقاط | المعنى |
|---|---:|---|
| <code>Easy</code> | 0 | أسئلة مباشرة من المحتوى. |
| <code>Medium</code> | 1 | تحتاج فهماً وربطاً متوسطاً. |
| <code>Hard</code> | 3 | تحتاج تحليلاً أعلى ومزوداً أقوى غالباً. |

### 1.3 نقاط نوع المصدر

| نوع المصدر | النقاط | السبب |
|---|---:|---|
| <code>Images</code> | 1 | تحتاج قراءة بصرية أو OCR. |
| <code>Pdf</code> | 2 | قد يحتوي عدة صفحات، تنسيقاً، جداول، أو صوراً ممسوحة ضوئياً. |

### 1.4 نقاط عدد الملفات

| عدد الملفات | النقاط |
|---|---:|
| ملف واحد | 0 |
| ملفان | 1 |
| 3 ملفات فأكثر | 2 |

### 1.5 نقاط الحجم الإجمالي

| الحجم الإجمالي | النقاط |
|---|---:|
| حتى 1MB | 0 |
| أكبر من 1MB | 1 |
| أكبر من 4MB | 2 |
| أكبر من 8MB | 3 |

الحجم الكبير لا يعني دائماً محتوى أعقد، لكنه غالباً يعني أن القراءة أو الرفع أو المعالجة ستحتاج وقتاً وموارد أكثر.

### 2. تحويل النقاط إلى مستوى

| score | level |
|---|---|
| أقل أو يساوي <code>low_max</code> | <code>low</code> |
| أقل أو يساوي <code>medium_max</code> | <code>medium</code> |
| أعلى من ذلك | <code>high</code> |

الإعداد الحالي:

```php
'score_thresholds' => [
    'low_max' => 2,
    'medium_max' => 5,
],
```

### 2.1 أمثلة عملية لحساب النقاط

| المثال | الحساب | النتيجة |
|---|---|---|
| صورة واحدة، 5 أسئلة، Easy، حجم أقل من 1MB | question=0 + difficulty=0 + source=1 + count=0 + size=0 | score=1 => low |
| 3 صور، 15 سؤال، Medium، حجم 2MB | question=1 + difficulty=1 + source=1 + count=2 + size=1 | score=6 => high |
| PDF واحد، 10 أسئلة، Easy، حجم 800KB | question=0 + difficulty=0 + source=2 + count=0 + size=0 | score=2 => low |
| PDF واحد، 25 سؤال، Hard، حجم 5MB | question=2 + difficulty=3 + source=2 + count=0 + size=2 | score=9 => high |

### 3. اختيار chain حسب source_type

بعد تحديد level، يقرأ النظام:

```php
provider_routing.chains_by_source_type.{source_type}.{level}
```

إذا لم يجدها، يرجع إلى:

```php
provider_routing.chains.{level}
```

هذا مهم لأن الصور والـ PDF لهما طبيعة مختلفة:

| المصدر | ما يفضله النظام |
|---|---|
| Images | مزود يدعم raw vision أولاً، ثم مزودات extracted text. |
| Pdf | مزود يدعم raw file أو toMarkdown أولاً، ثم مزودات extracted text. |

### لماذا يوجد chains_by_source_type؟

قبل إضافة <code>chains_by_source_type</code> كانت السلسلة تعتمد فقط على level:

```php
chains.low
chains.medium
chains.high
```

لكن بعد إضافة مزودين كثيرين، أصبح هذا غير دقيق. فمثلاً:

- <code>openrouter</code> ممتاز للصور لكنه ليس مفعلاً للـ PDF.
- <code>cloudflare_workers_ai</code> ممتاز للصور raw، ويستطيع التعامل مع PDF عبر <code>toMarkdown</code>.
- <code>deepseek</code> لا يرى الصور أو الملفات مباشرة، لكنه جيد بعد استخراج النص.
- <code>ollama_local</code> مفيد كـ fallback محلي للصور فقط.

لذلك أصبح الترتيب حسب المصدر ضرورياً.

### 4. الترتيب الحالي للصور

```text
Images low/medium:
openrouter
-> cloudflare_workers_ai
-> gemini
-> ollama_cloud
-> huggingface
-> deepseek
-> ollama_local
```

```text
Images high:
gemini
-> openrouter
-> cloudflare_workers_ai
-> ollama_cloud
-> huggingface
-> deepseek
-> ollama_local
```

<div class="doc-card note">
تم إبقاء <code>ollama_local</code> دائماً آخر سلسلة الصور كـ fallback محلي.
</div>

#### لماذا هذا ترتيب الصور؟

| الترتيب | Provider | السبب |
|---:|---|---|
| 1 | <code>openrouter</code> | يدعم raw image ومناسب كبداية للصور. |
| 2 | <code>cloudflare_workers_ai</code> | يدعم raw image ومجاني ضمن حدود يومية. |
| 3 | <code>gemini</code> | قوي في الرؤية والملفات، لكنه ليس أول اختيار إذا أردنا تقليل الاعتماد على مزود واحد. |
| 4 | <code>ollama_cloud</code> | يستخدم extracted text، فيفيد إذا OCR جيد. |
| 5 | <code>huggingface</code> | extracted text عبر OpenAI-compatible endpoint. |
| 6 | <code>deepseek</code> | text-only بعد OCR. |
| 7 | <code>ollama_local</code> | fallback محلي أخير للصور. |

في مستوى <code>high</code> يبدأ النظام بـ <code>gemini</code> لأن الطلبات الثقيلة أو الصعبة تستفيد من model قوي متعدد الوسائط.

### 5. الترتيب الحالي للـ PDF

```text
Pdf low/medium:
cloudflare_workers_ai
-> gemini
-> ollama_cloud
-> huggingface
-> deepseek
```

```text
Pdf high:
gemini
-> cloudflare_workers_ai
-> ollama_cloud
-> huggingface
-> deepseek
```

#### لماذا هذا ترتيب PDF؟

| الترتيب | Provider | السبب |
|---:|---|---|
| 1 | <code>cloudflare_workers_ai</code> في low/medium | يمكنه استقبال raw file عبر <code>toMarkdown</code> ثم التوليد، وضمن free allocation. |
| 2 | <code>gemini</code> | يدعم PDF raw بشكل مباشر وقوي. |
| 3 | <code>ollama_cloud</code> | يعمل بالنص المستخرج. |
| 4 | <code>huggingface</code> | يعمل بالنص المستخرج. |
| 5 | <code>deepseek</code> | text-only، جيد كخيار متأخر بعد extraction. |

في مستوى <code>high</code> يبدأ النظام بـ <code>gemini</code> لأن PDF الصعب أو الكبير قد يحتاج فهماً مباشراً للملف، خاصة إن كان يحتوي تنسيقاً معقداً.

### 6. فلترة القدرات

حتى لو ظهر provider في chain، لا يستخدمه النظام إلا إذا:

1. مسجل داخل <code>providers</code>.
2. يدعم <code>source_type</code>.
3. يدعم input mode متاح runtime مثل:
   - <code>raw_image</code>
   - <code>raw_file</code>
   - <code>extracted_text</code>

مثال:

```php
'openrouter' => [
    'source_types' => ['Images'],
    'input_modes' => ['raw_image'],
],
```

هذا يعني أن <code>openrouter</code> لن يدخل سلسلة PDF حتى لو ظهر بالخطأ في config، لأن <code>AiProviderCapabilityService</code> سيزيله.

### 7. الفرق بين source_types و input_modes

| المفهوم | المعنى |
|---|---|
| <code>source_types</code> | هل المزود مناسب من ناحية نوع الطلب؟ Images أو Pdf. |
| <code>input_modes</code> | كيف يستطيع المزود استقبال المحتوى؟ raw image أو raw file أو extracted text. |

مثلاً:

```php
'deepseek' => [
    'source_types' => ['Images', 'Pdf'],
    'input_modes' => ['extracted_text'],
],
```

هذا لا يعني أن DeepSeek يرى الصور أو PDF مباشرة. بل يعني أن النظام يستطيع استخدامه مع الصور والـ PDF بشرط تحويل المحتوى إلى نص أولاً.

### 8. ماذا يحدث إذا فشل Provider؟

بعد اختيار السلسلة النهائية، الـ Orchestrator يجرب provider تلو الآخر.

| نوع الفشل | هل ينتقل للمزود التالي؟ |
|---|---|
| rate limit | نعم |
| temporary unavailable | نعم |
| connection failed | نعم |
| invalid response | نعم |
| unsupported source type | نعم |
| text extraction failed | نعم |
| content not educational | لا غالباً، لأنه حكم على المحتوى نفسه |
| auth failed | لا، لأنه إعداد خاطئ غالباً |

إذا كان الفشل retryable، قد يتم وضع provider في cooldown عبر <code>AiProviderHealthService</code>، ثم ينتقل النظام للمزود التالي.

### 9. ماذا يحدث إذا أصبحت السلسلة فارغة؟

قد تصبح السلسلة فارغة إذا:

- كل providers غير مسجلين.
- كل providers لا يدعمون <code>source_type</code>.
- كل providers لا يدعمون input mode المتاح.

عندها يحاول النظام:

1. استخدام <code>fallback_provider</code>.
2. إذا لم يصلح، يجلب أي provider مسجل يدعم نفس المصدر ونمط الإدخال.
3. إذا لم يوجد شيء، يفشل الطلب برسالة أن كل المزودين غير متاحين.

### 10. ملخص القرار النهائي

يمكن تلخيص قرار الاختيار بهذه القاعدة:

```text
اختر سلسلة حسب نوع المصدر ومستوى التعقيد
ثم احذف أي مزود غير مسجل أو غير قادر
ثم جرّب المزودين بالترتيب
ثم انتقل للتالي عند الفشل القابل للتجاوز
```

<div class="doc-card note">
هذا التصميم يسمح بإضافة Providers جديدة لاحقاً دون تغيير الـ Controller أو Service الأساسي. غالباً نضيف provider class، ثم config capabilities، ثم نضعه في السلسلة المناسبة.
</div>

## قراءة محتوى الصور والملفات للموديلات النصية

بعض المزودين لا يقبلون ملفات أو صور raw. أمثلة:

- <code>DeepSeek</code>
- <code>OllamaCloud</code> في تطبيقنا الحالي
- <code>HuggingFace</code> في تطبيقنا الحالي

لذلك النظام يحول محتوى الملف إلى نص قبل إرساله.

### PDF نصي

إذا كان PDF يحتوي نصاً حقيقياً، يستخدم النظام:

```text
pdftotext
```

داخل:

```php
PdfTextExtractor
```

ثم ينظف النص ويضعه داخل prompt.

### الصور

إذا كان المصدر صوراً والمزود يحتاج نصاً، يستخدم النظام:

```text
tesseract
```

داخل:

```php
ImageOcrTextExtractor
```

ليحول الصورة إلى نص. بعدها يتم تنسيق النص وإرساله للموديل.

### Cloudflare toMarkdown

في <code>CloudflareWorkersAiQuestionGenerationProvider</code>:

- صورة واحدة: ترسل raw image مباشرة إلى Vision model.
- PDF أو عدة صور: ترسل الملفات إلى Cloudflare <code>toMarkdown</code>، ثم يرسل Markdown الناتج إلى الموديل.

### حدود مهمة

<div class="doc-card warning">
حالياً <code>PdfTextExtractor</code> لا يعالج scanned PDF بشكل مثالي إذا كان PDF عبارة عن صور فقط. هذه ستكون مرحلة تحسين لاحقة: كشف PDF الممسوح ضوئياً ثم تحويل صفحاته إلى صور وتشغيل OCR عليها.
</div>

## ملاحظات تشغيل واختبار

### اختبارات مفيدة

```bash
sail artisan test --filter=AiQuestionGenerationRoutingPolicyTest
sail artisan test --filter=AiQuestionGenerationTextExtractionIntegrationTest
sail artisan test --filter=DeepSeekQuestionGenerationProviderTest
sail artisan test --filter=OllamaCloudQuestionGenerationProviderTest
sail artisan test --filter=HuggingFaceInferenceQuestionGenerationProviderTest
sail artisan test --filter=CloudflareWorkersAiQuestionGenerationProviderTest
```

### فحص binaries داخل Sail

```bash
sail exec laravel.test which pdfinfo pdftotext tesseract
```

### متغيرات البيئة المطلوبة حسب المزود

| Provider | المتغيرات |
|---|---|
| Gemini | <code>GEMINI_API_KEY</code> |
| OpenRouter | <code>OPENROUTER_API_KEY</code> |
| DeepSeek | <code>DEEPSEEK_API_KEY</code> |
| Ollama Cloud | <code>OLLAMA_API_KEY</code> |
| Hugging Face | <code>HUGGINGFACE_API_KEY</code> أو <code>HF_TOKEN</code> |
| Cloudflare Workers AI | <code>CLOUDFLARE_ACCOUNT_ID</code>, <code>CLOUDFLARE_AI_API_KEY</code> |
| Ollama Local | <code>OLLAMA_BASE_URL</code> ووجود Ollama container/model |

### حالات status في الطلب

| status | المعنى |
|---|---|
| <code>Pending</code> | الطلب أنشئ ولم يبدأ Job بعد. |
| <code>Processing</code> | Job بدأ المعالجة. |
| <code>Completed</code> | تم توليد الأسئلة وحفظها. |
| <code>Failed</code> | فشل التوليد، راجع <code>failure_code</code> و <code>failure_message</code>. |

<div class="doc-card note">
هذا التوثيق يعكس البنية الحالية للميزة بعد إضافة:
<br>
<span class="badge">Provider Capabilities</span>
<span class="badge">Source Type Routing</span>
<span class="badge">Text Extraction</span>
<span class="badge">Ollama Cloud</span>
<span class="badge">Hugging Face</span>
<span class="badge">Cloudflare Workers AI</span>
</div>
