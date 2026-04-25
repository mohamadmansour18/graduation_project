<div dir="rtl">

## 📚 الفهرس

- [🎯 الكلاس 1: TargetLevelPreferenceData](#-الكلاس-1-TargetLevelPreferenceData)
    - [🧩 وظيفة هذا الكلاس باختصار 1](#-وظيفة-هذا-الكلاس-باختصار-1)

- [🎯 الكلاس 2: UserDiscoveryRawData](#-الكلاس-2-UserDiscoveryRawData)
    - [🧩 وظيفة هذا الكلاس باختصار 2](#-وظيفة-هذا-الكلاس-باختصار-2)

- [🎯 الكلاس 3: UserDiscoveryProfileData](#-الكلاس-3-UserDiscoveryProfileData)
    - [🧩 وظيفة هذا الكلاس باختصار 3](#-وظيفة-هذا-الكلاس-باختصار-3)

- [🎯 الكلاس 4: DiscoveryContextData](#-الكلاس-4-DiscoveryContextData)
    - [🧩 وظيفة هذا الكلاس باختصار 4](#-وظيفة-هذا-الكلاس-باختصار-4)

- [🎯 الكلاس 5: TestCandidateData](#-الكلاس-5-TestCandidateData)
    - [🧩 وظيفة هذا الكلاس باختصار 5](#-وظيفة-هذا-الكلاس-باختصار-5)
      
# 🎯 الكلاس 1: TargetLevelPreferenceData

## 🧠 ما وظيفته؟
هذا كلاس يحمل نتيجة الـ resolver
يعني بعد أن ننتهي من تحليل تعليم المستخدم، نضع النتيجة هنا.

## ❓لماذا نحتاجه؟
لأننا لا نريد أن يرجع الـ resolver Arrays عشوائية غير واضحة.

بل نريد نتيجة رسمية واضحة تقول:

- هذه المستويات الأساسية
- هذه المستويات الثانوية
- هذه المستويات العامة
- وهذه درجة الثقة

## 📦 الحقول الخاصة بهذا الكلاس

- `primaryLevels`
- `secondaryLevels`
- `broadLevels`
- `confidence`
- `reason`

## 🔍 شرح كل حقل

###  primaryLevels
المستويات الأقرب للمستخدم جدًا.

###  secondaryLevels
مستويات منطقية لكن أقل قربًا.

###  broadLevels
مستويات عامة جدًا، مثل:

معلومات عامة

### confidence
ما مدى ثقتنا في هذا الـ mapping
مثل:

- `high`
- `medium`
- `low`

###  reason
سبب القرار، مفيد جدًا للفهم والـ debugging.

مثل:

- `school_stage_matched`
- `university_year_matched`
- `education_level_only`

## 🧩 وظيفة هذا الكلاس باختصار 1

> ✅ النتيجة الرسمية النهائية لتحليل target levels الخاصة بالمستخدم

---

# 🎯 الكلاس 2: UserDiscoveryRawData

## ❓ لماذا نحتاجه؟
لأن البيانات الخارجة من الـ <span dir="ltr">Repository</span> ستكون:

- ما تزال خام
- لم تتحول بعد إلى <span dir="ltr">weights</span>
- ولم تتحول بعد إلى <span dir="ltr">target level preferences</span>

فإذا أعدناها كـ <span dir="ltr">Array</span> فوضوية، سنعود إلى نفس المشكلة القديمة.

لذلك أفضّل أن يكون عندنا :

- <span dir="ltr">DTO</span> للبيانات الخام
- ثم
- <span dir="ltr">DTO</span> للبيانات المحوّلة النهائية

## 📦 ما الذي سيحمله؟

سحمل الحقول التالية :

- `userId`
- `educationLevel`
- `schoolStage`
- `universityName`
- `universityDepartment`
- `universityYear`
- `interestSelections`

## 🔍 شرح الحقول

### - userId
هو المستخدم الحالي.

### - educationLevel
القيمة القادمة من  
<span dir="ltr">user_onboarding_profiles.education_level</span>

### - schoolStage
القيمة من  
<span dir="ltr">user_school_profiles.school_stage</span>  
إذا وجدت.

### - universityName
من  
<span dir="ltr">user_university_profiles.university_name</span>  
إذا وجدت.

### - universityDepartment
من  
<span dir="ltr">user_university_profiles.department</span>  
إذا وجدت.

### - universityYear
من  
<span dir="ltr">user_university_profiles.university_year</span>  
إذا وجدت.

### - interestSelections

هذه ليست <span dir="ltr">interestIds</span> فقط،  
بل قائمة خام تحتوي:

- <span dir="ltr">interest_id</span>
- <span dir="ltr">slot_no</span>

## 🧩 وظيفة هذا الكلاس باختصار 2

> ✅ الحاوية التي تحمل بيانات المستخدم الخام قبل تفسيرها

---

# 🎯 الكلاس 3: UserDiscoveryProfileData

## 🧠 ما هو؟
هذا <span dir="ltr">DTO</span> داخلي


## ❓ ما معنى DTO؟
يعني كلاس هدفه **حمل البيانات بشكل منظم بين الطبقات**.

بكلمات أبسط:
بدل أن نمرر <span dir="ltr">Array</span> كبيرة فيها 12 مفتاحًا بين:
- <span dir="ltr">Service</span>
- <span dir="ltr">Resolver</span>
- <span dir="ltr">Repository</span>

نضع كل شيء في كلاس واحد واضح:
- الاسم
- الحقول
- المعنى

## 🎯 لماذا نحتاجه؟
لأننا نريد أن يصبح عندنا كائن واحد يقول:

> هذه هي صورة المستخدم التي يحتاجها نظام التوصية

## 📦 ماذا سيحمل؟

الحقول التالية :

- `userId`
- `educationLevel`
- `schoolStage`
- `universityName`
- `universityDepartment`
- `universityYear`
- `interestIds`
- `weightedInterests`
- `preferredTargetLevels`
- `secondaryTargetLevels`


## 🔍 شرح كل حقل

### - userId
هوية المستخدم الحالية.

### - educationLevel
القيمة القادمة من <span dir="ltr">onboarding</span>.

مثلًا:
- مدرسة
- جامعة
- خريج
- ماجستير
- دكتوراه

### - schoolStage
إذا كان المستخدم من فئة المدرسة، نخزن المرحلة :

- ابتدائي
- إعدادي
- ثانوي

### - universityName
اسم الجامعة إذا كانت الحالة التعليمية تحتاجه.

### - universityDepartment
القسم أو الاختصاص.

### - universityYear
السنة الجامعية الحالية أو المرحلة التي وصل إليها.

### - interestIds
قائمة معرفات الاهتمامات بشكل بسيط:

### - weightedInterests
هذه أهم من السابقة
ليست مجرد ids، بل أوزان.
مثال:

```php
[
  4 => 5,
  7 => 4,
  12 => 3,
  15 => 2,
]
```
### - preferredTargetLevels
هذه القائمة أهم ناتج في هذا الجزء
وهي target levels الأقرب للمستخدم.

### - secondaryTargetLevels
هذه target levels مقبولة لكن أقل قربًا
نحتاجها للفهم المرن ولـ fallback لاحقًا.

## 🧩 وظيفة هذا الكلاس باختصار 3

> ✅ الحقيبة الرسمية التي تحمل صورة المستخدم إلى محرك التوصية

---

# 🎯 الكلاس 4: DiscoveryContextData

## ❓ لماذا نحتاجه؟
يخبرنا في أي شاشة نحن؟ وأي تبويب؟ وكم عنصر نريد عرضه؟ وكم عنصر نريد جلبه أوليًا قبل الترتيب النهائي؟

## 📦 ماذا سيحمل؟

الحقول التالية :

- `DiscoveryScreen`
- `DiscoveryTab`
- `limit`
- `candidatePoolLimit`

## 🔍 شرح كل حقل

### - DiscoveryScreen
هذا المتغير يحدد أي شاشة داخل التطبيق تطلب البيانات

### - DiscoveryTab
هذا المتغير يحدد التبويب داخل الشاشة
لأن الشاشة الواحدة قد تحتوي Tabs مختلفة، وكل Tab له منطق مختلف

مثلًا:
- جديد
- رائج
- الأكثر تقدما
- مجاني

### - limit
هذا هو العدد النهائي المطلوب عرضه للمستخدم
يعني لو الشاشة تريد عرض 10 عناصر فقط، فهنا تكون القيمة 10

### - candidatePoolLimit
هذا ليس العدد النهائي للعرض، بل عدد العناصر الأولية التي سنجلبها كمرشحين قبل التصفية أو الترتيب النهائي

## 🧩 وظيفة هذا الكلاس باختصار 4

> ✅ ان يجمع لك كل المعطيات التي تصف طلب الاكتشاف (التوصية) داخل Object واحد

---

# 🎯 الكلاس 5: TestCandidateData

## ❓ لماذا نحتاجه؟
هذا الكلاس يقول أنا أحتفظ ببيانات اختبار واحد مرشح ، بالشكل الذي يحتاجه نظام الاكتشاف أو الـ ranking داخليًا

## 📦 ماذا سيحمل؟

الحقول التالية :

- `creatorUserId`
- `title`
- `description`
- `price`
- `targetLevel`
- `publishedAt`
- `participantsCount`
- `likesCount`
- `averageRating`
- `interestIds`
- `matchedInterestIds`
- `matchedByTargetLevel`

## 🔍 شرح كل حقل

### - creatorUserId
هذا هو معرف المستخدم الذي أنشأ الاختبار

### - title
عنوان الاختبار

### - description
وصف الاختبار

### - price
سعر الاختبار ويأخذ قيمة null انه من الممكن ان يكون مجانيا

### - targetLevel
المستوى المستهدف لهذا الاختبار

مثلًا:
- سنة أولى جامعة
- ماجستير
- معلومات عامة

هذا يفيد جدًا في التوصية، لأن الاختبار إذا كان مناسبًا لمستوى المستخدم، غالبًا فرصته أعلى في الظهور.

### - publishedAt
تاريخ نشر الاختبار بعد موافقة الأدمن

### - participantsCount
عدد المشاركين في الاختبار و هذا مؤشر مهم على الشعبية أو النشاط

### - likesCount
عدد الإعجابات أيضًا مؤشر اجتماعي على جاذبية أو شعبية الاختبار.

### - averageRating
متوسط تقييم الاختبار

### - interestIds
هذه هي كل الاهتمامات المرتبطة بهذا الاختبار

### - matchedInterestIds
هي الاهتمامات المشتركة بين المستخدم والاختبار

### - matchedByTargetLevel
هذا متغير Boolean يقول:
إذا كان الاختبار مناسبًا للمستوى العلمي للمستخدم

## 🧩 وظيفة هذا الكلاس باختصار 5

> ✅ هذا الكلاس يمثل : اختبارًا واحدًا مرشحًا للتوصية

---
</div>
