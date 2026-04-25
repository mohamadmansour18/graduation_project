<div dir="rtl">

## 📚 الفهرس

- [🎯 الكلاس 1: UserDiscoveryProfileService](#-الكلاس-1-userdiscoveryprofileservice)
    - [🧩 وظيفة هذا الكلاس باختصار](#-وظيفة-هذا-الكلاس-باختصار)

# 🎯 الكلاس 1: UserDiscoveryProfileService

## 🧠 ما هو؟
هذا هو **المنسّق الرئيسي (Orchestrator)** لهذه الطبقة.

## ⚙️ ما وظيفته؟

هو لا يقوم بأي من العمليات التالية بشكل مباشر:

- ❌ لا يجلب البيانات من قاعدة البيانات
- ❌ لا يفسّر المستوى الدراسي
- ❌ لا يحسب الأوزان

بل يقوم بـ:

> ✅ **تجميع جميع هذه الأجزاء وربطها معًا بشكل منظم**


## 🔄 كيف يعمل بالتسلسل؟

### 🥇 الخطوة 1
يستقبل:

- `userId`


### 🥈 الخطوة 2
ينادي:

- `UserDiscoveryProfileRepository`

ليحصل على:

- `UserDiscoveryRawData`

### 🥉 الخطوة 3
ينادي:

- `UserInterestWeightResolver`

على:

- `interestSelections`


### 🏅 الخطوة 4
ينادي:

- `UserTargetLevelPreferenceResolver`

على:

- `UserDiscoveryRawData`

### 🎯 الخطوة 5
يقوم ببناء:

- `UserDiscoveryProfileData`

### 🔚 الخطوة 6
يرجع النتيجة إلى:

- الطبقة الأعلى


## ❓ لماذا هذا الكلاس مهم جدًا؟

لأنه يمنع الطبقة الأعلى (مثل `TestDiscoveryService`) من التعامل مع الفوضى الداخلية.

### ❌ بدون هذا الكلاس:

سيضطر `TestDiscoveryService` إلى:

- جلب onboarding
- جلب school
- جلب interests
- تحليل البيانات
- استخراج target levels

### ✅ معه:

سيقول فقط:

> **أعطني UserDiscoveryProfile**

👉 وهذا تصميم نظيف جدًا (Clean Architecture)

## 🧪 ما هي الدالة المقترحة؟

```php
buildForUser(int $userId): UserDiscoveryProfileData
```

## ⚠️ ما هي الـ Edge Cases التي يجب أن يتعامل معها؟

### 1️⃣ بيانات خام ناقصة

- لا يرمي أخطاء عبثية
- بل يبني `profile` بأفضل ما هو متاح


### 2️⃣ لا توجد Interests

يعيد:

```php
interestIds = []
weightedInterests = []
```

### 3️⃣ لا توجد Interests
يعيد:

- broad fallback
- confidence منخفض

### 4️⃣ المستخدم غير موجود
يرمي استثناء Business واضح
لأن المستخدم الحالي يفترض أن يكون authenticated أصلًا

### 🧩 وظيفة هذا الكلاس باختصار
> المصنع الذي يبني صورة المستخدم الكاملة لنظام التوصية
</div>
