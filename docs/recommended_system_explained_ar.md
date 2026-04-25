<div dir="rtl">

<style>:root {
  --bg: #0f172a;
  --panel: #111827;
  --panel-2: #0b1220;
  --text: #e5e7eb;
  --muted: #94a3b8;
  --accent: #38bdf8;
  --accent-2: #22c55e;
  --warning: #f59e0b;
  --border: #1f2937;
  --code-bg: #020617;
  --shadow: 0 10px 30px rgba(0,0,0,.25);
}

body {
  font-family: "Segoe UI", Tahoma, Arial, sans-serif;
  line-height: 1.95;
  color: var(--text);
  background: linear-gradient(180deg, #020617 0%, #0f172a 100%);
  margin: 0;
  padding: 0;
}

.wrapper {
  max-width: 1100px;
  margin: 0 auto;
  padding: 28px;
}

.hero {
  background: linear-gradient(135deg, rgba(56,189,248,.18), rgba(34,197,94,.12));
  border: 1px solid rgba(56,189,248,.28);
  border-radius: 24px;
  padding: 28px;
  box-shadow: var(--shadow);
  margin-bottom: 22px;
}

.hero h1 {
  margin: 0 0 10px;
  font-size: 2rem;
  color: #f8fafc;
}

.hero p {
  margin: 0;
  color: #dbeafe;
}

.badges {
  margin-top: 14px;
}

.badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 999px;
  margin: 4px 0 0 8px;
  font-size: .92rem;
  background: rgba(15,23,42,.55);
  border: 1px solid rgba(148,163,184,.25);
  color: #e2e8f0;
}

.card {
  background: rgba(17,24,39,.92);
  border: 1px solid var(--border);
  border-radius: 22px;
  padding: 24px;
  margin: 18px 0;
  box-shadow: var(--shadow);
}

.toc {
  background: rgba(2,6,23,.75);
  border: 1px solid rgba(56,189,248,.22);
}

.toc h2 {
  margin-top: 0;
  color: #f8fafc;
}

.toc ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.toc li {
  margin: 10px 0;
  padding: 0;
}

.toc a {
  color: #bae6fd;
  text-decoration: none;
}

.toc a:hover {
  color: white;
  text-decoration: underline;
}

h2, h3, h4 {
  color: #f8fafc;
}

h2 {
  border-right: 4px solid var(--accent);
  padding-right: 12px;
  margin-top: 0;
}

h3 {
  color: #c4b5fd;
}

p, li {
  color: var(--text);
}

small, .muted {
  color: var(--muted);
}

.note, .tip, .warn {
  border-radius: 18px;
  padding: 14px 16px;
  margin: 14px 0;
  border: 1px solid transparent;
}

.note {
  background: rgba(56,189,248,.08);
  border-color: rgba(56,189,248,.25);
}

.tip {
  background: rgba(34,197,94,.08);
  border-color: rgba(34,197,94,.25);
}

.warn {
  background: rgba(245,158,11,.08);
  border-color: rgba(245,158,11,.25);
}

code {
  background: var(--code-bg);
  color: #cbd5e1;
  padding: 2px 8px;
  border-radius: 8px;
  font-family: Consolas, Monaco, monospace;
}

pre {
  background: var(--code-bg);
  color: #dbeafe;
  padding: 16px;
  border-radius: 18px;
  overflow-x: auto;
  border: 1px solid rgba(148,163,184,.18);
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin: 14px 0;
  background: rgba(2,6,23,.35);
  border-radius: 16px;
  overflow: hidden;
}

th, td {
  padding: 12px 14px;
  border-bottom: 1px solid rgba(148,163,184,.14);
  text-align: right;
  vertical-align: top;
}

th {
  background: rgba(56,189,248,.12);
  color: #f8fafc;
}

tr:last-child td {
  border-bottom: none;
}

hr {
  border: none;
  height: 1px;
  background: linear-gradient(to left, transparent, rgba(148,163,184,.35), transparent);
  margin: 28px 0;
}

.kpi {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-top: 12px;
}

.kpi .box {
  background: rgba(2,6,23,.55);
  border: 1px solid rgba(148,163,184,.16);
  border-radius: 18px;
  padding: 14px;
}

.kpi .box strong {
  display: block;
  color: #f8fafc;
  margin-bottom: 6px;
}

.footer {
  text-align: center;
  color: var(--muted);
  margin: 30px 0 10px;
}
</style>

<div class="wrapper">

<div class="hero">
  <h1>شرح نظام التوصية في المشروع</h1>
  <p>
    هذا الملف يوثّق نظام <strong>Recommendation / Discovery Engine</strong> الذي قمنا ببنائه داخل المشروع،
    بطريقة مرتبة ومبسطة ومهنية، بحيث يمكنك الرجوع إليه لاحقًا لفهم:
    كيف يبدأ التنفيذ، وما وظيفة كل كلاس، وكيف يتم اختيار الاختبارات وترتيبها، وما الذي يدخل في حساب النقاط،
    وما الفرق بين API الـ Home و API المختبر.
  </p>
  <div class="badges">
    <span class="badge">Laravel 10</span>
    <span class="badge">RTL Arabic</span>
    <span class="badge">Route → Controller → Request → Service → Repository</span>
    <span class="badge">Recommendation Engine</span>
  </div>
</div>

<div class="card toc">
  <h2 id="toc">فهرس المحتوى</h2>
  <ul>
    <li><a href="#sec-1">1) الفكرة العامة لنظام التوصية</a></li>
    <li><a href="#sec-2">2) ما الذي يشمله محرك التوصية الحالي</a></li>
    <li><a href="#sec-3">3) الصورة الكبيرة في سطر واحد</a></li>
    <li><a href="#sec-4">4) خط التنفيذ الكامل في API الـ Home</a></li>
    <li><a href="#sec-5">5) شرح طبقة فهم المستخدم</a></li>
    <li><a href="#sec-6">6) شرح طبقة اختيار المرشحين Candidate Selection</a></li>
    <li><a href="#sec-7">7) شرح طبقة Ranking وحساب النقاط</a></li>
    <li><a href="#sec-8">8) شرح تجهيز بيانات العرض وطبقة الـ API</a></li>
    <li><a href="#sec-9">9) الفرق بين Home و Lab</a></li>
    <li><a href="#sec-10">10) أين تعدّل إذا أردت تغيير سلوك النظام</a></li>
    <li><a href="#sec-11">11) الرسم الذهني النهائي الكامل</a></li>
    <li><a href="#sec-12">12) كيف تتذكر النظام بعد شهر</a></li>
    <li><a href="#sec-13">13) شرح آلية مطابقة الاهتمامات والمستوى</a></li>
    <li><a href="#sec-14">14) شرح آلية حساب النقاط بالتفصيل</a></li>
    <li><a href="#sec-15">15) الملخص التنفيذي النهائي</a></li>
  </ul>
</div>

<div class="card" id="sec-1">
  <h2>1) الفكرة العامة لنظام التوصية</h2>
  <p>
    نظام التوصية عندنا ليس مجرد استعلام قاعدة بيانات يعيد بعض الاختبارات، بل هو <strong>منظومة كاملة</strong>
    هدفها أن تجيب على السؤال التالي:
  </p>
  <div class="note">
    <strong>من بين كل الاختبارات الموجودة في النظام، ما هي الاختبارات الأنسب لهذا المستخدم الآن، وبأي ترتيب؟</strong>
  </div>
  <p>وللإجابة عن هذا السؤال، قسمنا العمل إلى مراحل واضحة:</p>
  <ol>
    <li>نفهم المستخدم.</li>
    <li>نختار مجموعة اختبارات مرشحة فقط.</li>
    <li>نحسب نقاطًا لكل اختبار مرشح.</li>
    <li>نرتب النتائج حسب التاب المطلوب.</li>
    <li>نجهز البيانات النهائية للواجهة.</li>
    <li>نرجع JSON مرتب وواضح.</li>
  </ol>
  <div class="tip">
    هذه الفكرة أفضل بكثير من وضع كل المنطق داخل Controller أو داخل Query واحدة ضخمة، لأنها قابلة للتوسع والصيانة والاختبار.
  </div>
</div>

<div class="card" id="sec-2">
  <h2>2) ما الذي يشمله محرك التوصية الحالي</h2>
  <p>محرك التوصية الحالي يتكون من 5 طبقات رئيسية:</p>

  <div class="kpi">
    <div class="box">
      <strong>1. فهم المستخدم</strong>
      اهتماماته، ترتيبها، مستواه الدراسي، والـ target levels الأقرب له.
    </div>
    <div class="box">
      <strong>2. اختيار المرشحين</strong>
      استخراج مجموعة اختبارات مرشحة بدل العمل على كل الاختبارات دفعة واحدة.
    </div>
    <div class="box">
      <strong>3. ترتيب المرشحين</strong>
      حساب score لكل مرشح بحسب التاب المطلوب.
    </div>
    <div class="box">
      <strong>4. تجهيز بيانات العرض</strong>
      جلب بيانات صاحب الاختبار والاهتمامات والتفاصيل المطلوبة للواجهة.
    </div>
    <div class="box">
      <strong>5. إخراج النتيجة</strong>
      تحويل كل ذلك إلى JSON مناسب للـ Home أو المختبر.
    </div>
  </div>
</div>

<div class="card" id="sec-3">
  <h2>3) الصورة الكبيرة في سطر واحد</h2>
  <div class="note">
    <strong>الـ API يستقبل الطلب ← يبني صورة المستخدم ← يجلب اختبارات مرشحة ← يحسب نقاطها ← يرتبها ← يجلب بيانات العرض ← يعيد JSON للواجهة</strong>
  </div>
  <p>إذا حفظت هذا السطر، ستتمكن دائمًا من فهم الفلو حتى لو نسيت أسماء بعض الكلاسات.</p>
</div>

<div class="card" id="sec-4">
  <h2>4) خط التنفيذ الكامل في API الـ Home</h2>
  <p>سنشرح التنفيذ من أول نقطة دخول حتى آخر خطوة في الاستجابة.</p>

  <h3>الخطوة 1: Route</h3>
  <p><strong>وظيفته:</strong> يربط رابط الـ URL بالـ Controller المناسب.</p>
  <p><strong>الدخل:</strong> طلب HTTP من التطبيق.</p>
  <p><strong>الخرج:</strong> لا يعيد بيانات، فقط يمرر الطلب إلى الـ Controller.</p>

  <h3>الخطوة 2: ListHomeRecommendedTestsRequest</h3>
  <p><strong>وظيفته:</strong> يتحقق من صحة قيمة <code>tab</code> ويضع القيمة الافتراضية <code>trending</code> إذا لم تُرسل.</p>
  <p><strong>الدخل:</strong> الطلب الخام من المستخدم.</p>
  <p><strong>الخرج:</strong> Request منظّمة ومتحقق منها.</p>

  <h3>الخطوة 3: HomeRecommendedTestsController</h3>
  <p><strong>وظيفته:</strong> يقرأ التاب، يقرأ المستخدم الحالي، يستدعي Service الشاشة، ثم يعيد JSON.</p>
  <p><strong>الدخل:</strong> Request validated + المستخدم الحالي.</p>
  <p><strong>الخرج:</strong> Response JSON.</p>
  <div class="warn">
    الـ Controller لا يجب أن يحسب score ولا يجلب من قاعدة البيانات مباشرة ولا يقرر target levels.
  </div>

  <h3>الخطوة 4: HomeRecommendedTestsService</h3>
  <p><strong>وظيفته:</strong> يحدد أن الشاشة <code>HOME</code>، يحدد التاب، يبني <code>DiscoveryContextData</code>، يستدعي <code>TestDiscoveryService</code>، ثم يجلب تفاصيل العرض ويدمجها مع score.</p>
  <p><strong>الدخل:</strong> <code>userId</code> و <code>tab</code>.</p>
  <p><strong>الخرج:</strong> Array جاهزة تقريبًا للـ Resource / Response.</p>

  <h3>الخطوة 5: DiscoveryContextData</h3>
  <p><strong>وظيفته:</strong> يحمل سياق التنفيذ: الشاشة، التاب، limit، وحجم candidate pool.</p>
  <p><strong>الدخل:</strong> screen + tab + limit.</p>
  <p><strong>الخرج:</strong> Object منظم.</p>

  <h3>الخطوة 6: TestDiscoveryService</h3>
  <p><strong>هذا هو القلب الرئيسي للمحرك.</strong></p>
  <ol>
    <li>يبني صورة المستخدم.</li>
    <li>يختار المرشحين.</li>
    <li>يحدد سياسة الترتيب المناسبة.</li>
    <li>يحسب score لكل مرشح.</li>
    <li>يرتب النتائج.</li>
    <li>يرجع أفضل النتائج.</li>
  </ol>
  <p><strong>الدخل:</strong> <code>userId</code> + <code>DiscoveryContextData</code>.</p>
  <p><strong>الخرج:</strong> Array من <code>RankedCandidateData</code>.</p>
</div>

<div class="card" id="sec-5">
  <h2>5) شرح طبقة فهم المستخدم</h2>
  <p>هذه الطبقة مسؤولة عن الإجابة على السؤال: <strong>من هو هذا المستخدم من زاوية التوصية؟</strong></p>

  <h3>UserDiscoveryProfileService</h3>
  <p><strong>وظيفته:</strong> يبني ملف المستخدم التوصياتي الكامل.</p>
  <p><strong>الدخل:</strong> <code>userId</code>.</p>
  <p><strong>الخرج:</strong> <code>UserDiscoveryProfileData</code>.</p>
  <p><strong>كيف يعمل؟</strong> يستدعي 3 أجزاء:</p>
  <ol>
    <li><code>UserDiscoveryProfileRepository</code></li>
    <li><code>UserInterestWeightResolver</code></li>
    <li><code>UserTargetLevelPreferenceResolver</code></li>
  </ol>

  <h3>UserDiscoveryProfileRepository</h3>
  <p><strong>وظيفته:</strong> يجلب البيانات الخام للمستخدم من قاعدة البيانات.</p>
  <p><strong>يقرأ من:</strong></p>
  <ul>
    <li><code>user_onboarding_profiles</code></li>
    <li><code>user_school_profiles</code></li>
    <li><code>user_university_profiles</code></li>
    <li><code>user_interest_selections</code></li>
  </ul>
  <p><strong>الدخل:</strong> <code>userId</code>.</p>
  <p><strong>الخرج:</strong> <code>UserDiscoveryRawData</code>.</p>
  <div class="note">
    هذا الكلاس لا يحسب أوزانًا ولا target levels، بل فقط يجلب المواد الخام.
  </div>

  <h3>UserDiscoveryRawData</h3>
  <p><strong>وظيفته:</strong> يحمل البيانات الخام كما خرجت من قاعدة البيانات.</p>
  <p><strong>يحتوي على:</strong> education level، school stage، university year، department، interest selections.</p>

  <h3>UserInterestWeightResolver</h3>
  <p><strong>وظيفته:</strong> يحول ترتيب الاهتمامات <code>slot_no</code> إلى أوزان فعلية.</p>
  <p>مثلًا:</p>
  <ul>
    <li>الاهتمام الأول = وزن أعلى</li>
    <li>الاهتمام الخامس = وزن أقل</li>
  </ul>
  <p><strong>الدخل:</strong> interest selections الخام.</p>
  <p><strong>الخرج:</strong> <code>weightedInterests</code>.</p>

  <h3>UserTargetLevelPreferenceResolver</h3>
  <p><strong>وظيفته:</strong> يترجم بيانات التعليم إلى target levels مفهومة لنظام التوصية.</p>
  <p><strong>الدخل:</strong> <code>UserDiscoveryRawData</code>.</p>
  <p><strong>الخرج:</strong> <code>TargetLevelPreferenceData</code>.</p>
  <p><strong>ما الذي ينتجه؟</strong></p>
  <ul>
    <li><code>primaryLevels</code></li>
    <li><code>secondaryLevels</code></li>
    <li><code>broadLevels</code></li>
    <li><code>confidence</code></li>
    <li><code>reason</code></li>
  </ul>

  <h3>TargetLevelBuckets</h3>
  <p><strong>وظيفته:</strong> يحتفظ بمجموعات target levels الجاهزة مثل:</p>
  <ul>
    <li>كل صفوف الابتدائي</li>
    <li>كل صفوف الإعدادي</li>
    <li>كل صفوف الثانوي</li>
    <li>كل سنوات الجامعة</li>
    <li>سنوات الجامعة المتقدمة</li>
    <li>معلومات عامة</li>
  </ul>

  <h3>TargetLevelPreferenceData</h3>
  <p><strong>وظيفته:</strong> يمثل تقرير المستوى الدراسي للمستخدم بالنسبة للتوصية.</p>

  <h3>UserDiscoveryProfileData</h3>
  <p><strong>وظيفته:</strong> هو الملف النهائي الجاهز الذي يستعمله باقي المحرك.</p>
  <p><strong>يحتوي على:</strong></p>
  <ul>
    <li><code>interestIds</code></li>
    <li><code>weightedInterests</code></li>
    <li><code>targetLevelPreference</code></li>
    <li>وبعض بيانات التعليم المفيدة للتتبع والشرح</li>
  </ul>
</div>

<div class="card" id="sec-6">
  <h2>6) شرح طبقة اختيار المرشحين Candidate Selection</h2>
  <p>بعد أن فهمنا المستخدم، ننتقل إلى السؤال التالي:</p>
  <div class="note">
    <strong>من بين كل الاختبارات الموجودة، ما هي الاختبارات التي تستحق أن تدخل مرحلة المنافسة أصلًا؟</strong>
  </div>

  <h3>TestCandidateSelectionService</h3>
  <p><strong>وظيفته:</strong> يطلب candidate pool من الـ repository.</p>
  <p><strong>الدخل:</strong> <code>UserDiscoveryProfileData</code> + <code>DiscoveryContextData</code>.</p>
  <p><strong>الخرج:</strong> Array من <code>TestCandidateData</code>.</p>

  <h3>TestDiscoveryRepository</h3>
  <p><strong>وظيفته:</strong> يبني candidate pool من 3 سلال:</p>
  <ol>
    <li>اختبارات تطابق الاهتمامات</li>
    <li>اختبارات تطابق target level</li>
    <li>fallback عام</li>
  </ol>
  <p><strong>الدخل:</strong> <code>UserDiscoveryProfileData</code> + <code>DiscoveryContextData</code>.</p>
  <p><strong>الخرج:</strong> Array من <code>TestCandidateData</code>.</p>
  <p><strong>الشروط الأساسية التي يطبقها:</strong></p>
  <ul>
    <li><code>test_type = public</code></li>
    <li><code>review_status = approved</code></li>
    <li>استبعاد اختبارات المستخدم نفسه</li>
    <li>إذا كان التاب <code>free</code> يأخذ المجاني فقط</li>
  </ul>
  <p><strong>ما الذي يفعله داخليًا؟</strong></p>
  <ul>
    <li>يجلب IDs من bucket الاهتمامات</li>
    <li>يجلب IDs من bucket المستوى</li>
    <li>يجلب IDs من fallback</li>
    <li>يدمجها بدون تكرار</li>
    <li>يجلب بيانات الاختبارات نفسها</li>
    <li>يجلب اهتمامات كل اختبار</li>
    <li>يبني <code>TestCandidateData</code></li>
  </ul>

  <h3>TestCandidateData</h3>
  <p><strong>وظيفته:</strong> يمثل اختبارًا واحدًا دخل إلى مرحلة الـ ranking.</p>
  <p><strong>يحتوي على:</strong></p>
  <ul>
    <li><code>id</code></li>
    <li><code>title</code></li>
    <li><code>description</code></li>
    <li><code>price</code></li>
    <li><code>targetLevel</code></li>
    <li><code>publishedAt</code></li>
    <li><code>participantsCount</code></li>
    <li><code>likesCount</code></li>
    <li><code>averageRating</code></li>
    <li><code>interestIds</code></li>
    <li><code>matchedInterestIds</code></li>
    <li><code>matchedByTargetLevel</code></li>
    <li><code>candidateBucket</code></li>
  </ul>
</div>

<div class="card" id="sec-7">
  <h2>7) شرح طبقة Ranking وحساب النقاط</h2>
  <p>بعد أن صار عندنا مرشحون، نحتاج أن نعرف: <strong>كيف نرتبهم؟</strong></p>

  <h3>RankingPolicyResolver</h3>
  <p><strong>وظيفته:</strong> يختار سياسة الترتيب المناسبة حسب التاب.</p>
  <p><strong>مثال:</strong></p>
  <ul>
    <li><code>trending</code> → <code>TrendingRankingPolicy</code></li>
    <li><code>new</code> → <code>NewRankingPolicy</code></li>
    <li><code>free</code> → <code>FreeRankingPolicy</code></li>
    <li><code>most_participated</code> → <code>MostParticipatedRankingPolicy</code></li>
  </ul>

  <h3>RankingPolicy (interface)</h3>
  <p><strong>وظيفته:</strong> يفرض شكلًا موحدًا على كل Policy.</p>
  <p>كل policy يجب أن:</p>
  <ul>
    <li>تأخذ <code>candidate</code></li>
    <li>وتأخذ <code>userProfile</code></li>
    <li>وترجع <code>RankedCandidateData</code></li>
  </ul>

  <h3>BaseRankingPolicy</h3>
  <p><strong>وظيفته:</strong> يحتوي الدوال المشتركة بين جميع السياسات.</p>
  <p><strong>أمثلة على ما يحتويه:</strong></p>
  <ul>
    <li>حساب <code>interestScore</code></li>
    <li>حساب <code>targetLevelScore</code></li>
    <li>حساب <code>freshnessScore</code></li>
    <li>حساب <code>participantsScore</code></li>
    <li>حساب <code>likesScore</code></li>
    <li>حساب <code>ratingScore</code></li>
    <li>حساب <code>bucketScore</code></li>
  </ul>

  <h3>TrendingRankingPolicy</h3>
  <p><strong>منطقها:</strong> خليط من:</p>
  <ul>
    <li>الاهتمامات</li>
    <li>المستوى</li>
    <li>المشاركات</li>
    <li>الإعجابات</li>
    <li>التقييم</li>
    <li>حداثة خفيفة</li>
  </ul>
  <p>فكرة هذا التاب: الاختبار يكون حيًا، شعبيًا، ومناسبًا للمستخدم.</p>

  <h3>NewRankingPolicy</h3>
  <p><strong>منطقها:</strong> الحداثة هي العامل الأقوى، مع الاحتفاظ بالاهتمامات والمستوى وشعبية خفيفة.</p>
  <p>الهدف: الاختبار الجديد لا يُظلم لأنه لا يملك interactions كثيرة بعد.</p>

  <h3>MostParticipatedRankingPolicy</h3>
  <p><strong>منطقها:</strong> المشاركات هي العامل الأقوى، لكن مع بقاء الاهتمامات والمستوى والتقييم وحداثة خفيفة.</p>

  <h3>FreeRankingPolicy</h3>
  <p><strong>منطقها:</strong> المرشحون أصلًا مجانيون، ثم نمزج الاهتمامات والمستوى والحداثة وبعض الشعبية والتقييم.</p>

  <h3>RankedCandidateData</h3>
  <p><strong>وظيفته:</strong> يحمل النتيجة بعد حساب score.</p>
  <p><strong>يحتوي على:</strong></p>
  <ul>
    <li><code>candidate</code></li>
    <li><code>score</code></li>
    <li><code>scoreBreakdown</code></li>
  </ul>

  <div class="tip">
    هذا الكلاس مهم جدًا لأننا لا نريد فقط معرفة من فاز، بل نريد أن نعرف <strong>لماذا</strong> فاز.
  </div>
</div>

<div class="card" id="sec-8">
  <h2>8) شرح تجهيز بيانات العرض وطبقة الـ API</h2>

  <h3>RecommendedTestDetailsRepository</h3>
  <p><strong>وظيفته:</strong> يأخذ IDs الاختبارات المرتبة، ثم يجلب كل تفاصيل العرض المطلوبة للواجهة.</p>
  <p><strong>يجلب مثلًا:</strong></p>
  <ul>
    <li>اسم صاحب الاختبار</li>
    <li>عدد اختباراته المنشورة</li>
    <li>عدد متابعيه</li>
    <li>هل هو موثق</li>
    <li>عنوان الاختبار ووصفه</li>
    <li>اهتماماته</li>
    <li>المستوى</li>
    <li>عدد الأسئلة</li>
    <li>التقييم</li>
    <li>السعر</li>
    <li>تاريخ النشر عند الحاجة</li>
  </ul>

  <h3>Resources</h3>
  <p><strong>وظيفتها:</strong> تشكيل JSON النهائي للواجهة.</p>
  <p>مثلًا:</p>
  <ul>
    <li>في Home: Resource تعيد owner + test + recommendation</li>
    <li>في المختبر: Resource للـ featured cards وResource للقائمة العادية</li>
  </ul>

  <h3>Controllers</h3>
  <p><strong>وظيفتها:</strong> استقبال الطلب، استدعاء Service المناسبة، ثم إعادة response موحد.</p>
</div>

<div class="card" id="sec-9">
  <h2>9) الفرق بين Home و Lab</h2>

  <h3>Home</h3>
  <ul>
    <li>الهدف: اكتشاف سريع وشخصي</li>
    <li>النتيجة: 10 اختبارات فقط</li>
    <li>التابات: رائج، جديد، الأكثر تقدمًا</li>
    <li>السلوك: نتيجة مباشرة مرتبة من محرك التوصية</li>
  </ul>

  <h3>Lab</h3>
  <ul>
    <li>الهدف: استكشاف أوسع</li>
    <li>التابات: رائج، جديد، مجاني، الأكثر تقدمًا</li>
    <li>الصفحة الأولى: 4 بطاقات مميزة أعلى تقييمًا من داخل نافذة النتائج الموصى بها</li>
    <li>ثم قائمة paginated لباقي النتائج</li>
    <li>الصفحات التالية: لا نكرر featured cards</li>
  </ul>

  <div class="warn">
    في المختبر، featured cards لا تعني أعلى 4 في النظام كله، بل أعلى 4 تقييمًا <strong>من داخل نافذة النتائج التي مرّت أصلًا عبر محرك التوصية</strong>.
  </div>
</div>

<div class="card" id="sec-10">
  <h2>10) أين تعدّل إذا أردت تغيير سلوك النظام</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>إذا أردت تعديل...</th>
          <th>اذهب إلى...</th>
          <th>السبب</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>فهم المستخدم</td>
          <td><code>UserDiscoveryProfileService</code> / <code>UserTargetLevelPreferenceResolver</code> / <code>UserInterestWeightResolver</code></td>
          <td>لأنها طبقة تحليل المستخدم</td>
        </tr>
        <tr>
          <td>اختيار المرشحين</td>
          <td><code>TestDiscoveryRepository</code> / <code>TestCandidateSelectionService</code></td>
          <td>لأنها طبقة candidate selection</td>
        </tr>
        <tr>
          <td>طريقة حساب النقاط</td>
          <td><code>BaseRankingPolicy</code> أو policy التاب المحدد</td>
          <td>لأن score يحسب هناك</td>
        </tr>
        <tr>
          <td>شكل الـ API</td>
          <td>Controller + Resource + screen-specific service</td>
          <td>لأن هذه طبقة الإخراج</td>
        </tr>
        <tr>
          <td>بيانات العرض</td>
          <td><code>RecommendedTestDetailsRepository</code></td>
          <td>لأنها المسؤولة عن ترطيب النتائج</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="card" id="sec-11">
  <h2>11) الرسم الذهني النهائي الكامل</h2>
  <pre>
Request
↓
Controller
↓
Screen Service (Home / Lab)
↓
TestDiscoveryService
↓
UserDiscoveryProfileService
↓
UserDiscoveryProfileRepository + Resolvers
↓
UserDiscoveryProfileData
↓
TestCandidateSelectionService
↓
TestDiscoveryRepository
↓
TestCandidateData[]
↓
RankingPolicyResolver
↓
Specific RankingPolicy
↓
RankedCandidateData[]
↓
RecommendedTestDetailsRepository
↓
Resource
↓
JSON Response
  </pre>
</div>

<div class="card" id="sec-12">
  <h2>12) كيف تتذكر النظام بعد شهر</h2>
  <p>لا تحفظ أسماء الكلاسات أولًا. احفظ الأدوار أولًا.</p>
  <ol>
    <li><strong>أفهم المستخدم</strong></li>
    <li><strong>أختار المرشحين</strong></li>
    <li><strong>أحسب النقاط</strong></li>
    <li><strong>أرتبهم</strong></li>
    <li><strong>أجلب بيانات العرض</strong></li>
    <li><strong>أرجع JSON</strong></li>
  </ol>
  <p>ثم بعد ذلك اربط كل دور بالكلاس المسؤول عنه.</p>
</div>

<div class="card" id="sec-13">
  <h2>13) شرح آلية مطابقة الاهتمامات والمستوى</h2>

  <h3>أولًا: مطابقة الاهتمامات</h3>
  <p>المستخدم يملك اهتمامات اختارها أثناء onboarding، والاختبار يملك اهتمات مرتبطة به.</p>
  <p>نحن نقارن بين:</p>
  <ul>
    <li><code>user interest ids</code></li>
    <li><code>test interest ids</code></li>
  </ul>
  <p>وأي تقاطع بينهما يعني أن الاختبار قريب من اهتمام المستخدم.</p>
  <div class="tip">
    نحن لا نعتمد فقط على وجود الاهتمام، بل أيضًا على <strong>ترتيبه</strong> عند المستخدم، لأن الاهتمام الأول أهم من الخامس.
  </div>

  <h3>ثانيًا: وزن الاهتمامات</h3>
  <p>استخدمنا مبدأ بسيطًا وواضحًا:</p>
  <ul>
    <li>slot 1 = وزن 5</li>
    <li>slot 2 = وزن 4</li>
    <li>slot 3 = وزن 3</li>
    <li>slot 4 = وزن 2</li>
    <li>slot 5 = وزن 1</li>
  </ul>
  <p>إذا وافق الاختبار اهتمامًا في المركز الأول، يأخذ نقاطًا أكثر من مطابقته لاهتمام في المركز الخامس.</p>

  <h3>ثالثًا: مطابقة المستوى الدراسي</h3>
  <p>المستخدم لا يحمل <code>target_level</code> مباشرة، بل يحمل بيانات تعليمية:</p>
  <ul>
    <li>مدرسة</li>
    <li>جامعة</li>
    <li>ماجستير</li>
    <li>دكتوراه</li>
    <li>خريج</li>
  </ul>
  <p>لذلك نستخدم <code>UserTargetLevelPreferenceResolver</code> لتحويل هذه اللغة إلى target levels مفهومة داخل الاختبارات.</p>

  <h3>رابعًا: أنواع target levels الناتجة</h3>
  <ul>
    <li><strong>Primary:</strong> أقرب مستوى للمستخدم</li>
    <li><strong>Secondary:</strong> مستوى قريب ومنطقي لكنه أقل دقة</li>
    <li><strong>Broad:</strong> مستويات عامة جدًا مثل معلومات عامة</li>
  </ul>

  <h3>خامسًا: أمثلة سريعة</h3>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>حالة المستخدم</th>
          <th>Primary</th>
          <th>Secondary</th>
          <th>Broad</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>مدرسة + ابتدائي</td>
          <td>كل صفوف الابتدائي</td>
          <td>فارغ</td>
          <td>معلومات عامة</td>
        </tr>
        <tr>
          <td>جامعة + سنة ثالثة</td>
          <td>سنة ثالثة جامعة</td>
          <td>سنة ثانية + سنة رابعة</td>
          <td>معلومات عامة</td>
        </tr>
        <tr>
          <td>ماجستير</td>
          <td>ماجستير</td>
          <td>فارغ</td>
          <td>معلومات عامة</td>
        </tr>
        <tr>
          <td>دكتوراه</td>
          <td>دكتوراه</td>
          <td>ماجستير</td>
          <td>معلومات عامة</td>
        </tr>
        <tr>
          <td>خريج</td>
          <td>سنوات الجامعة المتقدمة</td>
          <td>سنوات الجامعة المبكرة + ماجستير</td>
          <td>معلومات عامة</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="warn">
    target level في المرحلة الحالية <strong>Bonus</strong> وليس <strong>Hard Filter</strong>، أي أنه يزيد score لكنه لا يستبعد الاختبار مباشرة في أغلب الحالات.
  </div>
</div>

<div class="card" id="sec-14">
  <h2>14) شرح آلية حساب النقاط بالتفصيل</h2>
  <p>هذه من أهم أجزاء النظام، لأنك طلبت معرفة ما الذي يدخل في حساب النقاط بالضبط.</p>

  <h3>أ) الأشياء التي تدخل في حساب النقاط</h3>
  <ul>
    <li><strong>مطابقة الاهتمامات</strong></li>
    <li><strong>مطابقة target level</strong></li>
    <li><strong>حداثة الاختبار</strong></li>
    <li><strong>عدد المشاركات</strong></li>
    <li><strong>عدد الإعجابات</strong></li>
    <li><strong>متوسط التقييم</strong></li>
    <li><strong>نوع bucket الذي دخل منه المرشح</strong></li>
  </ul>

  <h3>ب) interest score</h3>
  <p>نأخذ الاهتمامات المشتركة بين المستخدم والاختبار، ثم نجمع أوزانها.</p>
  <p>مثال:</p>
  <ul>
    <li>الاهتمام الأول وزنه 5</li>
    <li>الاهتمام الثالث وزنه 3</li>
    <li>إذا وافق الاختبار الاثنين، يصبح raw = 8</li>
  </ul>
  <p>ثم نضرب هذه القيمة في multiplier تختلف قليلًا من policy إلى أخرى.</p>

  <h3>ج) target level score</h3>
  <ul>
    <li>إذا كان المستوى Primary → bonus قوي</li>
    <li>إذا كان Secondary → bonus متوسط</li>
    <li>إذا كان Broad → bonus خفيف</li>
    <li>إذا لم يطابق شيئًا → 0</li>
  </ul>

  <h3>د) freshness score</h3>
  <p>كلما كان الاختبار أحدث زادت نقاطه.</p>
  <p>استخدمنا شرائح زمنية بسيطة وواضحة بدل معادلات معقدة.</p>

  <h3>هـ) participants score</h3>
  <p>اعتمدنا على عدد المشاركات، لكن باستخدام <code>log</code> بدل القيمة الخام.</p>
  <p><strong>السبب:</strong> حتى لا يسيطر اختبار قديم جدًا أو ضخم جدًا على كل النتائج إلى الأبد.</p>

  <h3>و) likes score</h3>
  <p>نفس الفكرة، باستخدام <code>log</code> أيضًا، لكن بوزن أقل من المشاركات.</p>

  <h3>ز) rating score</h3>
  <p>نحوّل متوسط التقييم إلى score إضافي، بحيث الاختبار الأعلى جودة يأخذ دفعة إضافية.</p>

  <h3>ح) bucket score</h3>
  <p>إذا دخل الاختبار من bucket الاهتمامات أو target level، نعطيه bonus خفيف، لأن دخوله من bucket أقوى يعني أنه أصلًا أقرب للمستخدم.</p>

  <h3>ط) لماذا تختلف الأوزان بين التابات؟</h3>
  <p>لأن معنى كل تاب مختلف:</p>
  <ul>
    <li><strong>Trending:</strong> نريد خليطًا من الشعبية والاهتمام والجودة</li>
    <li><strong>New:</strong> الحداثة هي العامل الأقوى</li>
    <li><strong>Most Participated:</strong> المشاركات هي العامل الأقوى</li>
    <li><strong>Free:</strong> الاختبار مجاني أصلًا، فنرتبه حسب الجودة والاهتمام والحداثة</li>
  </ul>

  <h3>ي) نتيجة score النهائية</h3>
  <p>كل Policy تجمع العناصر المناسبة لها، ثم ترجع:</p>
  <ul>
    <li><code>score</code></li>
    <li><code>scoreBreakdown</code></li>
  </ul>
  <p>وهذا ما يسمح لنا أن نطبع النتيجة في الـ API ونفهم لماذا حصل الاختبار على ترتيبه.</p>
</div>

<div class="card" id="sec-15">
  <h2>15) الملخص التنفيذي النهائي</h2>
  <p>الآن عندنا محرك توصية عملي ومنظم يتكون من:</p>
  <ol>
    <li>طبقة تفهم المستخدم</li>
    <li>طبقة تختار اختبارات مرشحة</li>
    <li>طبقة تحسب نقاطًا مختلفة حسب التاب</li>
    <li>طبقة ترتب النتائج</li>
    <li>طبقة تجلب بيانات العرض</li>
    <li>طبقة تعيد API مناسبة للـ Home والمختبر</li>
  </ol>

  <div class="tip">
    أهم شيء تتذكره دائمًا:<br>
    <strong>أفهم المستخدم ← أختار المرشحين ← أحسب النقاط ← أرتب ← أجهز العرض ← أرجع JSON</strong>
  </div>

  <p>
    هذا الملف يمثل مرجعًا ممتازًا للمرحلة الحالية من نظام التوصية،
    والمرحلة التالية المنطقية بعده هي: <strong>تقوية المحرك من ناحية السرعة والأداء وقوة التوصية</strong>،
    مثل تحسين candidate windows، وإدخال cache، ثم precomputed ranks لاحقًا.
  </p>
</div>

<div class="footer">
  تم إنشاء هذا الملف لتوثيق نظام التوصية الحالي داخل المشروع بشكل احترافي ومفهوم وقابل للرجوع إليه لاحقًا.
</div>

</div>
</div>
