# AI Database Context — Multi-Module Exam Management System (Laravel)

> هذا الملف هو **المرجع النصي المعتمد** لفهم قاعدة البيانات العلاقية الخاصة بالمشروع من أجل:
> - تحليل الـ ERD بالكامل
> - فهم الجداول والعلاقات والقيود
> - بناء الـ API لاحقًا
> - مساعدة أي أداة ذكاء اصطناعي (ChatGPT / Claude / Gemini / Cursor / Copilot...) على فهم بنية النظام بدون الحاجة إلى إعادة شرح الداتابيز يدويًا في كل مرة

## 1) نطاق الملف

هذا الملف يغطي التصميم المعتمد حاليًا للـ Modules التالية:

1. Authentication
2. Tests
3. Library Material
4. Test Folder
5. Study Plan
6. Settings
7. Profile
8. Personal User Profiles
9. Admin Dashboard Statistics

## 2) قواعد عامة مهمة

### 2.1 قاعدة التسمية
- أسماء الجداول والحقول الواردة هنا هي **الأسماء المعتمدة فعليًا**.
- بعض الأسماء المعتمدة تحتوي على spelling غير مثالي أو typos بسيطة، لكنها **جزء من التصميم الحالي** ويجب عدم “تصحيحها تلقائيًا” أثناء بناء الـ API إلا إذا تقرر لاحقًا تنفيذ migration صريح.

### 2.2 حقول التتبع الزمنية
- جميع الجداول المصممة في المشروع تعتمد وجود:
  - `created_at`
  - `updated_at`
- حتى لو لم تُذكر في كل مرة ضمن السرد المختصر، فهي معتمدة كجزء من التصميم.

### 2.3 الجداول المصدرية مقابل الجداول الملخصة
يوجد نوعان من الجداول:

1. **Transactional / Source of Truth**
   - مثل: `users`, `test`, `study_task`, `library_material`, `test_purchases`
   - هذه هي الجداول الأساسية التي تمثل البيانات الحقيقية

2. **Summary / Read Models**
   - مثل: `user_stats_summary`, `user_yearly_test_stats`, `admin_yearly_financial_stats`
   - هذه الجداول مشتقة من الجداول الأصلية وتستخدم لتحسين الأداء وسرعة العرض في لوحة التحكم أو صفحات الإحصائيات

### 2.4 ملاحظات على enum values
- بعض الحقول enum ذُكرت كـ enum دون تثبيت كل القيم النصية النهائية داخل هذا الملف
- بالنسبة للـ API:
  - يجب اعتبار هذه الحقول **قِيَمًا ثابتة business-wise**
  - وتُدار عادة عبر constants / enums في كود Laravel
- عندما تكون القيم معروفة دلاليًا من المتطلبات، ذُكرت ملاحظاتها في الشرح

### 2.5 ملاحظات خاصة بالأسماء المعتمدة كما هي
الأسماء التالية **مقصودة كما اعتمدها التصميم الحالي**:
- `users.onboarding_complered_at`
- `test_interset_selections`
- `test_bookmarks.use_id`
- `test_download_logs.downloadd_at`

إذا تقرر لاحقًا تصحيحها، يجب أن يكون ذلك عبر migration واضح، وليس عبر “تصحيح ضمني” أثناء بناء الـ API.

---

# 3) Module: Authentication

## 3.1 الجداول المعتمدة

```text
roles(id, name)

users(
  id, role_id, name, email, password, email_verified_at,
  onboarding_completed_at, last_login_at, gender ,is_academically_verified ,academically_verified_at
)

auth_otp_codes(
  id, user_id, purpose enum, code_hash, send_to_email,
  expires_at, consumed_at, revoked_at, attempts_count
)

user_bans(
  id, user_id, imposed_by_user_id, ban_type enum, reason,
  starts_at, ends_at, lifted_by_user_id, lifted_at
)

user_onboarding_profiles(
  id, user_id, discovery_source enum,
  education_level enum, last_completed_step
)

user_university_profiles(
  id, user_id, university_name enum, university_year, department enum
)

user_school_profiles(
  id, user_id, school_stage enum
)

user_interest_selections(
  id, user_id, interest_id, slot_no
)

interest_categories(id, title)

interests(id, interest_category_id, name)

user_stats_summary(
  id, year, total_completed_mobile_users,
  male_completed_mobile_users, female_completed_mobile_users
)

user_stats_by_discovery_source(
  id, year, discovery_source enum, completed_mobile_users_count
)
```

## 3.2 الغرض من الـ Module
هذا الـ Module مسؤول عن:
- إدارة الحسابات والأدوار
- التحقق بالبريد الإلكتروني عبر OTP
- OTP لإعادة تعيين كلمة المرور
- onboarding للمستخدم العادي
- ربط المستخدم باهتماماته
- الحظر المؤقت أو الدائم
- إحصائيات المستخدمين الأساسية السريعة للوحة التحكم

## 3.3 العلاقات الأساسية
- `roles` 1 —— N `users`
- `users` 1 —— N `auth_otp_codes`
- `users` 1 —— N `user_bans`
- `users` 1 —— 1 `user_onboarding_profiles`
- `users` 1 —— 0..1 `user_university_profiles`
- `users` 1 —— 0..1 `user_school_profiles`
- `users` 1 —— N `user_interest_selections`
- `interest_categories` 1 —— N `interests`
- `interests` 1 —— N `user_interest_selections`

## 3.4 قواعد عمل مهمة
- كل الحسابات داخل جدول `users` واحد
- التمييز بينها عبر `role_id`
- أدوار النظام دلاليًا:
  - `Owner`
  - `Supervisor`
  - `User`
- المستخدم العادي فقط يمر بـ:
  - تأكيد البريد
  - onboarding
- المشرف والمالك:
  - لا يمران بـ onboarding
  - لا يحتاجان تأكيد بريد ضمن هذا الـ workflow
- لا يتم منح token للمستخدم إلا بعد **تسجيل الدخول الفعلي**
- في حال كان:
  - البريد غير مؤكد
  - أو الـ onboarding غير مكتمل
  - أو الحساب محظور
  - فلا يسمح بتسجيل الدخول

## 3.5 قواعد الـ OTP
- جدول واحد موحد لكل من:
  - email verification
  - password reset
- الكود صالح لمدة 5 دقائق
- الكود يخزن بشكل `code_hash`
- `consumed_at` يعني أن الكود استُخدم
- `revoked_at` يعني أنه ألغي
- `attempts_count` لحصر عدد المحاولات

## 3.6 قواعد الحظر
- `ban_type` دلاليًا:
  - `temporary`
  - `permanent`
- الحظر المؤقت:
  - من يوم واحد إلى 30 يومًا
- الحظر الدائم:
  - لا نهاية له
- يمكن فك الحظر وتخزين من قام بفكه

## 3.7 onboarding
- `user_onboarding_profiles` يحفظ:
  - مصدر معرفة التطبيق
  - المحافظة
  - المستوى الدراسي
  - آخر خطوة أنجزها المستخدم
- الجامعة والمدرسة لهما جداول تفصيلية منفصلة
- الاهتمامات:
  - أقصى حد 5
  - مرتبطة بجدول `interests`

## 3.8 الإحصائيات
- `user_stats_summary` و `user_stats_by_discovery_source` هي جداول summary
- الهدف منها:
  - سرعة العرض في لوحة التحكم
  - عدم احتساب الأرقام الثقيلة في كل مرة من الجداول الأصلية

---

# 4) Module: Tests

## 4.1 الجداول المعتمدة

```text
test(
  id, creator_user_id, title, description, test_type enum,
  difficulty_level enum, duration_seconds, pass_mark_percentage,
  language enum, price, target_level enum, review_status enum,
  current_approval_version, published_at, last_content_updated_at,
  question_count, preview_question_count, likes_count,
  bookmarks_count, downloads_count, reviews_count,
  participants_count, average_rating
)

test_interset_selections(id, test_id, interest_id, slot_no)

test_question(
  id, test_id, position, question_text, hint_text,
  is_preview, options_count
)

test_question_options(
  id, test_question_id, position, option_text, is_correct
)

test_purchases(
  id, test_id, buyer_user_id, seller_user_id,
  gross_amount, platform_fee_amount, seller_net_amount,
  currency, payment_provider, payment_reference,
  payment_status enum, purchased_at
)

test_attempts(id, test_id, user_id, mode enum)

test_reports(id, test_id, user_id, reason, description, reported_at)

test_review_rounds(
  id, test_id, round_no, reviewer_user_id,
  trigger_type enum, decision enum,
  based_on_approval_version, started_at, decided_at
)

test_revision_requests(
  id, test_review_round_id, test_id, revision_type,
  target_question_id, decision enum,
  created_by_user_id, resolved_at, problem_note
)

test_revision_change_logs(
  id, test_review_round_id, test_id, revision_request_id,
  revision_type enum, target_question_id , before_value, after_value,
  changed_by_user_id
)

test_status_histories(
  id, test_id, from_status enum, to_status enum,
  changed_by_user_id, note
)

test_bookmarks(id, test_id, use_id)

test_likes(id, test_id, user_id)

test_reviews(
  id, test_id, user_id, rating, review_text,
  helpful_yes_count, helpful_no_count
)

test_review_feedbacks(id, test_review_id, user_id, vote enum)

test_download_logs(id, test_id, user_id, downloadd_at)
```

## 4.2 الغرض من الـ Module
هذا الـ Module يدير:
- إنشاء اختبارات MCQ
- الأسئلة والخيارات
- التصنيفات العلمية المرتبطة بالاختبار
- الشراء والبيع
- الإعجاب والحفظ والتنزيل والمراجعات
- دورة المراجعة الإدارية
- البلاغات
- counters السريعة للواجهة والإحصائيات

## 4.3 العلاقات الأساسية
- `users` 1 —— N `test` عبر `creator_user_id`
- `test` 1 —— N `test_interset_selections`
- `interests` 1 —— N `test_interset_selections`
- `test` 1 —— N `test_question`
- `test_question` 1 —— N `test_question_options`
- `test` 1 —— N `test_purchases`
- `test` 1 —— N `test_attempts`
- `test` 1 —— N `test_reports`
- `test` 1 —— N `test_review_rounds`
- `test_review_rounds` 1 —— N `test_revision_requests`
- `test_review_rounds` 1 —— N `test_revision_change_logs`
- `test_question` 1 —— N `test_revision_change_logs`
- `test` 1 —— N `test_status_histories`
- `test` 1 —— N `test_bookmarks`
- `test` 1 —— N `test_likes`
- `test` 1 —— N `test_reviews`
- `test_reviews` 1 —— N `test_review_feedbacks`
- `test` 1 —— N `test_download_logs`


## 4.4 قواعد العمل
### نوع الاختبار
- `test_type` دلاليًا:
  - `public`
  - `private`

### الاختبار الخاص
- لا يمر بمراجعة المشرفين
- لا يملك preview sample خارجي
- لا يوجد عليه:
  - report
  - review
  - like (حسب ما انتهى إليه التصميم العملي المستخدم)
- يمكن تنزيله وحفظه بحسب صلاحيات المالك واستخدامات النظام

### الاختبار العام
- قد يكون:
  - مجانيًا (`price = null`)
  - أو مدفوعًا (`price > 0`)
- يمر بحالات مراجعة
- لا يظهر للعامة إلا بعد `approved`

### الأسئلة
- الاختبار من 5 إلى 100 سؤال
- كل سؤال من 2 إلى 5 خيارات
- لكل سؤال إجابة صحيحة واحدة
- `is_preview` يستخدم لتحديد أسئلة العينة الخارجية
- `question_count`, `preview_question_count` counters سريعة

### التفاعل
- العامة المجانية: إعجاب + حفظ + تنزيل + تقييم + إبلاغ
- العامة المدفوعة:
  - قبل الشراء: إعجاب + حفظ
  - بعد الشراء: يمكن التقييم والإبلاغ والتنزيل أيضًا
- `participants_count` يمثل عدد المتقدمين للاختبار
- `average_rating` هو متوسط تقييمات الاختبار

## 4.5 دورة المراجعة الإدارية
### الحالات الأساسية
- `new`
- `needs_revision`
- `under_review`
- `approved`
- `deleted`
- `reported`

### `current_approval_version`
- يمثل رقم آخر نسخة منشورة/معتمدة من الاختبار
- مثال:
  - `0`: لم يُعتمد بعد
  - `1`: أول اعتماد
  - `2`: أعيد اعتماده بعد مشكلة/بلاغ/تعديلات
- يتم **تحديثه في نفس سجل الاختبار**
- لا يُنشأ سجل جديد في `test`

### `test_review_rounds`
يمثل جولات المراجعة:
- الجولة الأولى عند الإرسال الأول
- جولة بعد إعادة الإرسال
- جولة بسبب البلاغات
- `based_on_approval_version` يثبت أن هذه الجولة تتعلق بأي نسخة معتمدة وقتها، حتى لو تغيّر `current_approval_version` لاحقًا

### `test_revision_requests`
يمثل طلبات التعديل التي يكتبها المشرف

### `test_revision_change_logs`
يمثل ماذا عدل صاحب الاختبار فعليًا، بحيث يرى المشرف قبل/بعد فقط للأشياء التي تغيّرت

## 4.6 الشراء والبيع
- `test_purchases` يخزن:
  - سعر الشراء الكامل
  - عمولة المنصة
  - صافي البائع
  - مزود الدفع
  - المرجع الخارجي
  - حالة الدفع
- `payment_reference` يمكن أن يحمل مرجع Stripe مثل:
  - `cs_...` (Checkout Session)
  - أو `pi_...` (PaymentIntent)

## 4.7 البلاغات
- البلاغات على الاختبار تخزن في `test_reports`
- يتم التمييز حسب:
  - `test_id`
  - `user_id`
  - `reason`
  - وواقع النشر/الموافقة عبر دورة المراجعة
- يحدد المشرف لاحقًا إن كانت البلاغات صحيحة أم لا عبر workflow الإداري

---

# 5) Module: Library Material

## 5.1 الجداول المعتمدة

```text
library_material(
  id, creator_user_id, imposed_by_user_id, title, description,
  content_kind enum, visibility_type enum, target_level enum,
  review_status enum, current_approval_version, published_at,
  asset_count, like_count, bookmarks_count, download_count
)

library_material_asset(
  id, library_material_id, asset_type, storage_disk,
  storage_path, original_name, mime_type, position
)

library_material_interest_selections(
  id, library_material_id, interest_id, slot_no
)

library_material_review_rounds(
  id, library_material_id, round_no, reviewer_user_id,
  trigger_type, decision, based_on_approval_version,
  started_at, decided_at
)

library_material_status_histories(
  id, library_material_id, from_status enum,
  to_status enum, changed_by_user_id, note
)

library_material_bookmarks(id, library_material_id, user_id)

library_material_likes(id, library_material_id, user_id)

library_material_download_logs(id, library_material_id, user_id)

library_material_reports(
  id, library_material_id, user_id,
  approval_version, reason, description, reported_at
)

library_material_report_reason_counters(
  id, library_material_id, approval_version,
  reason, reporters_count
)
```

## 5.2 الغرض من الـ Module
يدير:
- منشورات المكتبة العلمية
- الملفات أو مجموعات الصور
- الرؤية العامة/الخاصة
- المراجعة الإدارية
- الإعجاب والحفظ والتنزيل
- البلاغات
- counters السريعة

## 5.3 العلاقات الأساسية
- `users` 1 —— N `library_material`
- `library_material` 1 —— N `library_material_asset`
- `library_material` 1 —— N `library_material_interest_selections`
- `interests` 1 —— N `library_material_interest_selections`
- `library_material` 1 —— N `library_material_review_rounds`
- `library_material` 1 —— N `library_material_status_histories`
- `library_material` 1 —— N `library_material_bookmarks`
- `library_material` 1 —— N `library_material_likes`
- `library_material` 1 —— N `library_material_download_logs`
- `library_material` 1 —— N `library_material_reports`
- `library_material` 1 —— N `library_material_report_reason_counters`

## 5.4 قواعد العمل
- المنشور يكون إما:
  - `file`
  - أو `image_group`
- الملف:
  - أصل واحد فقط
- الصور:
  - من 1 إلى 3 صور كمنشور واحد
- أقصى عدد تصنيفات علمية للمحتوى: 3
- `visibility_type`:
  - `public`
  - `private`
- الخاص:
  - ينشر مباشرة
  - لا يمر بالمراجعة
  - لا يراه الآخرون
- العام:
  - يمر بمراجعة
  - لا يظهر للعامة إلا إذا كانت حالته `approved`

## 5.5 حالات المحتوى العام
- `new`
- `approved`
- `deleted`
- `reported`

## 5.6 current_approval_version
- نفس فلسفة الاختبارات:
  - كل إعادة اعتماد عامة ترفع النسخة
  - البلاغات تُربط بالنسخة المعتمدة التي حدثت عليها

## 5.7 البلاغات
- `library_material_reports`
  - تخزن البلاغات الفردية
- `library_material_report_reason_counters`
  - جدول summary سريع لعدد البلاغات حسب السبب والنسخة
- الهدف:
  - سهولة التحويل إلى `reported`
  - عزل البلاغات الكيدية القديمة عند إعادة النشر

---

# 6) Module: Test Folder

## 6.1 الجداول المعتمدة

```text
test_folder(
  id, creator_id, name, color_code,
  visibility_type, contained_test_type,
  tests_count, published_at
)

test_folder_bookmarks(id, test_folder_id, user_id)

test_folder_item(id, test_folder_id, test_id, position)
```

## 6.2 الغرض من الـ Module
يدير:
- مجلدات الاختبارات
- ربط الاختبارات بالمجلدات
- حفظ المجلدات في المحفوظات

## 6.3 العلاقات الأساسية
- `users` 1 —— N `test_folder` عبر `creator_id`
- `test_folder` 1 —— N `test_folder_item`
- `test` 1 —— N `test_folder_item`
- `test_folder` 1 —— N `test_folder_bookmarks`

## 6.4 قواعد العمل
- المجلد يحتوي اختبارات من نفس النوع فقط:
  - كلها عامة
  - أو كلها خاصة
- لا يجوز خلط العام والخاص
- `contained_test_type` يحدد نوع الاختبارات المسموح بها داخل المجلد
- أقصى عدد اختبارات في المجلد: 10
- لا يجوز إنشاء مجلد بدون اختبارات
- عند حذف المجلد:
  - لا تُحذف الاختبارات
  - يُحذف فقط المجلد وروابطه
- `tests_count` هو counter سريع
- `visibility_type`:
  - `public`
  - `private`

---

# 7) Module: Study Plan

## 7.1 الجداول المعتمدة

```text
study_subject(id, user_id, name)

study_plan(
  id, user_id, title, emoji, start_date, end_date,
  daily_study_minutes, is_default, subjects_count,
  tasks_count, completed_tasks_count, missed_tasks_count,
  pending_tasks_count
)

study_plan_subject(
  id, study_plan_id, study_subject_id, slot_no
)

study_task(
  id, study_plan_id, study_plan_subject_id, task_group_uuid,
  title, description, start_date, end_date, start_time,
  duration_minutes_per_day, deadline_at,
  reminder_offset_minutes, priority enum, status enum,
  completed_at, missed_at, repeat_pattern enum,
  recurrence_end_date
)

study_task_occurrence(
  id, study_task_id, study_plan_id, occurrence_date,
  scheduled_start_time, scheduled_end_time, duration_minutes
)

study_task_subtask(
  id, study_task_id, title, position,
  is_completed, completed_at
)
```

## 7.2 الغرض من الـ Module
يدير:
- المواد الشخصية للمستخدم
- الخطط الدراسية
- ربط الخطة بالمواد
- المهام الرئيسية
- السجلات اليومية الفعلية للمهام
- المهام الفرعية
- counters والإحصائيات السريعة للخطة

## 7.3 العلاقات الأساسية
- `users` 1 —— N `study_subject`
- `users` 1 —— N `study_plan`
- `study_plan` 1 —— N `study_plan_subject`
- `study_subject` 1 —— N `study_plan_subject`
- `study_plan` 1 —— N `study_task`
- `study_plan_subject` 1 —— N `study_task`
- `study_task` 1 —— N `study_task_occurrence`
- `study_task` 1 —— N `study_task_subtask`
- `study_plan` 1 —— N `study_task_occurrence`

## 7.4 قواعد العمل
### الخطط
- كل مستخدم يستطيع إنشاء **5 خطط كحد أقصى**
- يجب أن تكون له **خطة افتراضية واحدة فقط**
- تاريخ نهاية الخطة يجب أن يكون بعد تاريخ البداية
- أقصى ساعات يومية: 12 ساعة = 720 دقيقة

### المواد
- المواد تنشأ مسبقًا بواسطة المستخدم
- كل خطة تحتوي أقصى 10 مواد
- `slot_no` في `study_plan_subject` يساعد على تنظيم وربط المواد

### المهام
- كل مهمة يجب أن ترتبط دائمًا **بمادة واحدة** من مواد نفس الخطة
- المهمة قد تكون:
  - ليوم واحد
  - أو ممتدة لعدة أيام
- `duration_minutes_per_day` هي مدة المهمة في **كل يوم**
- `deadline_at` = النهاية الدقيقة الفعلية للمهمة
- `end_date` = آخر يوم للمهمة
- الفرق بينهما مهم للأتمتة وتحديد المهام الفائتة

### حالات المهام
- `todo`
- `in_progress`
- `completed`
- `missed`

### التكرار
- `repeat_pattern` دلاليًا:
  - `none`
  - `weekly_1`
  - `weekly_2`
  - `weekly_3`
  - `weekly_4`
- التكرار ينتهي عبر:
  - `recurrence_end_date`
- إذا كانت المهمة ليوم واحد:
  - يمكن اختيار أكثر من يوم تكرار
- إذا كانت المهمة تمتد لأكثر من يوم:
  - يسمح بيوم تكرار واحد فقط

### الملاحظات الزمنية المهمة
- النظام يولد **مهام فعلية** + **occurrences فعلية**
- التداخل الزمني بين المهام في نفس اليوم **مسموح**
- لكن:
  - مجموع دقائق المهام اليومية لا يجوز أن يتجاوز `daily_study_minutes`

## 7.5 study_task_occurrence
هذا الجدول جوهري في المشروع لأنه:
- يسهل التحقق من الساعات اليومية
- يجعل جلب مهام اليوم مباشرًا
- يسهل جلب:
  - المهام القديمة
  - القادمة
  - المكتملة
- يدعم منطق التقويم اليومي للخطة الافتراضية

## 7.6 task_group_uuid
- يستخدم لتجميع المهام التي خرجت من نفس عملية التكرار
- يفيد لاحقًا في:
  - تعديل سلسلة كاملة
  - حذف سلسلة كاملة
  - معرفة أصل التكرار

---

# 8) Module: Settings

## 8.1 الجداول المعتمدة

```text
user_settings(
  id, user_id, task_reminders_enabled,
  week_starts_on enum, time_format enum, theme_mode enum
)

user_academic_verification_requests(
  id, user_id, status enum, submitted_at,
  reviewer_user_id, reviewed_at, rejection_reason
)

user_academic_verification_assets(
  id, verification_request_id, asset_type enum,
  storage_disk, storage_path, original_name,
  mime_type
)

user_yearly_study_stats(
  id, user_id, year, total_tasks_count,
  todo_tasks_count, in_progress_tasks_count,
  completed_tasks_count, missed_tasks_count
)

user_yearly_study_plan_stats(
  id, user_id, study_plan_id, year,
  total_tasks_count, todo_tasks_count,
  in_progress_tasks_count, completed_tasks_count,
  missed_tasks_count
)

user_yearly_test_stats(
  id, user_id, year, total_likes_received,
  total_reviews_received, total_bookmarks_received,
  published_tests_count, first_published_test_at,
  last_published_test_at
)

user_yearly_test_publish_month_stats(
  id, user_id, year, month_no, published_tests_count
)
```

## 8.2 الجداول المعاد استخدامها دون إعادة تصميم
- `users`
- `study_plan`
- `study_task`
- `study_task_occurrence`
- `test`
- `test_likes`
- `test_reviews`
- `test_bookmarks`
- `test_purchases`

## 8.3 الغرض من الـ Module
يدير:
- إعدادات المستخدم العامة
- تفعيل/تعطيل تذكيرات المهام
- بداية الأسبوع
- 12h / 24h
- dark / light mode
- طلبات التوثيق الأكاديمي
- الإحصائيات السنوية داخل الإعدادات
- تبويب الاختبارات المباعة (اعتمادًا على `test_purchases`)

## 8.4 قواعد العمل
### user_settings
- جدول one-to-one مع المستخدم
- `task_reminders_enabled`:
  - إذا كانت false فلا ترسل تنبيهات المهام حتى لو كانت المهمة نفسها تحتوي reminder
- `week_starts_on` يؤثر على العرض فقط
- `time_format` يؤثر على العرض فقط
- `theme_mode` للمظهر

### التوثيق الأكاديمي
- المستخدم لا يحق له إرسال طلب جديد إذا كان لديه طلب `pending`
- إذا رفض الطلب يمكنه إعادة التقديم لاحقًا بطلب جديد
- الملفات المرفقة:
  - شهادة جامعية
  - بطاقة هوية
- يتم مراجعتها يدويًا من قبل مشرف

### الإحصائيات السنوية
- `year` هو مفتاح العرض والتحليل
- إحصائيات الاختبارات السنوية تحتسب حسب **سنة التفاعل نفسه**
- الإحصائيات في شاشة الإعدادات سنوية حسب قيمة `year` المختارة

## 8.5 ملاحظة أداء اختيارية
تم اقتراح إضافة الحقلين التاليين إلى `users` لتسريع شارة التوثيق:
- `is_academically_verified`
- `academically_verified_at`

> هذه الإضافة **اقتراح اختياري** عند الدمج النهائي، وليست جزءًا إلزاميًا من التصميم المعتمد حتى هذه اللحظة.

---

# 9) Module: Profile

## 9.1 الجداول المعتمدة

```text
user_profile(
  id, user_id, phone, birth_date,
  avatar_disk, avatar_path, cover_disk,
  cover_path, profile_slug
)

user_follows(
  id, follower_user_id, followed_user_id
)

user_profile_stats(
  id, user_id, followers_count, following_count,
  published_tests_count, library_materials_count,
  folders_count, average_test_rating,
  total_test_likes_received,
  total_test_reviews_received,
  total_test_bookmarks_received
)
```

## 9.2 الجداول المعاد استخدامها
- `users`
- `user_onboarding_profiles`
- `user_university_profiles`
- `user_school_profiles`
- `user_interest_selections`
- `interests`
- `test`
- `library_material`
- `test_folder`
- `user_academic_verification_requests`

## 9.3 الغرض من الـ Module
يدير:
- بيانات البروفايل الإضافية
- صورة شخصية وصورة غلاف
- رابط بروفايل قابل للمشاركة
- نظام المتابعة
- counters سريعة للبروفايل
- عرض تابات:
  - الاختبارات
  - المحتوى
  - القوائم

## 9.4 قواعد العمل
- `phone` و `birth_date` يظهران **لصاحب الحساب فقط**
- `profile_slug`:
  - يولد مرة واحدة
  - غير قابل للتعديل
- `user_profile_stats` هو جدول summary سريع للبروفايل
- التوثيق الأكاديمي يمكن عرضه في البروفايل اعتمادًا على:
  - آخر طلب توثيق approved
  - أو الحقول الاختيارية المقترحة على `users`

## 9.5 العلاقات الأساسية
- `users` 1 —— 1 `user_profile`
- `users` 1 —— N `user_follows` كـ follower
- `users` 1 —— N `user_follows` كـ followed
- `users` 1 —— 1 `user_profile_stats`

---

# 10) Module: Personal User Profiles

## 10.1 ملاحظة تصميمية مهمة
هذا الـ Module **لا يحتاج جداول جديدة**.

هو يمثل “طريقة عرض” للبروفايل العام لمستخدم آخر باستخدام الجداول المعتمدة سابقًا، خصوصًا:
- `user_profile`
- `user_profile_stats`
- `user_onboarding_profiles`
- `user_university_profiles`
- `user_school_profiles`
- `user_interest_selections`
- `interests`
- `test`
- `library_material`
- `test_folder`

## 10.2 ما الذي يعرضه؟
### نظرة عامة
- المستوى الدراسي
- مكان الإقامة
- الجنس
- تاريخ الانضمام
- الاهتمامات العلمية
- متوسط التقييم
- إجمالي الإعجابات على الاختبارات
- إجمالي التعليقات على الاختبارات
- إجمالي مرات حفظ الاختبارات

### التابات
- الاختبارات
- القوائم
- المحتوى

### المشاركة
- رابط البروفايل عبر `profile_slug`

## 10.3 ملاحظة مهمة
- تم اعتماد أن هذا الـ Module لا يضيف جداول جديدة
- وإنما يعتمد فقط على الجداول الموجودة مع توسيع `user_profile_stats` بالحقول:
  - `average_test_rating`
  - `total_test_likes_received`
  - `total_test_reviews_received`
  - `total_test_bookmarks_received`

---

# 11) Module: Admin Dashboard Statistics

## 11.1 الجداول المعتمدة

```text
admin_yearly_financial_stats(
  id, year, sold_purchase_count, distinct_sold_tests_count,
  gross_sales_amount, users_profit_amount,
  platform_net_profit_amount,
  average_monthly_sales_amount,
  average_monthly_platform_profit_amount,
  most_purchased_test_id,
  most_purchased_test_purchase_count
)

admin_yearly_financial_month_stats(
  id, year, month_no, sold_purchase_count,
  distinct_sold_tests_count, gross_sales_amount,
  users_profit_amount, platform_net_profit_amount
)

admin_yearly_test_sales_stats(
  id, year, test_id, purchase_count,
  gross_sales_amount, users_profit_amount,
  platform_net_profit_amount
)

admin_yearly_test_activity_month_stats(
  id, year, month_no, published_tests_count,
  likes_count, reviews_count, downloads_count
)

admin_yearly_library_material_activity_month_stats(
  id, year, month_no, published_materials_count, likes_count
)
```

## 11.2 الجداول المعاد استخدامها
- `test_purchases`
- `test`
- `test_likes`
- `test_reviews`
- `test_download_logs`
- `library_material`
- `library_material_likes`
- `user_stats_summary`
- `user_stats_by_discovery_source`

## 11.3 الغرض من الـ Module
هذا الـ Module يقدم جداول summary سريعة للوحة التحكم الإدارية:
- إحصائيات مالية سنوية
- إحصائيات مالية شهرية
- إحصائيات مبيعات كل اختبار
- مخطط شهري للاختبارات
- مخطط شهري للمحتوى

## 11.4 الفرق بين الجداول المالية الثلاثة
### admin_yearly_financial_stats
- صف واحد لكل سنة
- ملخص السنة كاملة

### admin_yearly_financial_month_stats
- 12 صفًا لكل سنة (أو صف لكل شهر مستخدم حسب استراتيجية التوليد)
- تفصيل شهري داخل السنة

### admin_yearly_test_sales_stats
- صف واحد لكل اختبار بيع في سنة معينة
- تفصيل أداء كل اختبار ماليًا داخل السنة

## 11.5 قواعد الاحتساب
### مالية
من `test_purchases`:
- `sold_purchase_count`: عدد عمليات الشراء الناجحة
- `distinct_sold_tests_count`: عدد الاختبارات المختلفة التي بيعت
- `gross_sales_amount`: مجموع `gross_amount`
- `users_profit_amount`: مجموع `seller_net_amount`
- `platform_net_profit_amount`: مجموع `platform_fee_amount`

### نشاط الاختبارات شهريًا
- `published_tests_count` حسب شهر `test.published_at`
- `likes_count` حسب شهر `test_likes.created_at`
- `reviews_count` حسب شهر `test_reviews.created_at`
- `downloads_count` حسب شهر `test_download_logs.downloadd_at`

### نشاط المحتوى شهريًا
- `published_materials_count` حسب شهر `library_material.published_at`
- `likes_count` حسب شهر `library_material_likes.created_at`

---

# 12) خريطة العلاقات عبر الـ Modules

## 12.1 مركز النظام
الجدول المركزي هو:
- `users`

ويرتبط معه بشكل مباشر أو غير مباشر:
- Authentication
- Study Plan
- Tests
- Library Material
- Folders
- Settings
- Profile

## 12.2 الجداول المرجعية متعددة الاستخدام
### interests
هذا الجدول يستخدم في أكثر من Module:
- onboarding للمستخدم
- تصنيفات الاختبارات
- تصنيفات المحتوى العلمي

## 12.3 جداول summary / read models
هذه الجداول لا تمثل المصدر الأساسي للبيانات، بل تستخدم لتحسين الأداء:
- `user_stats_summary`
- `user_stats_by_discovery_source`
- `user_yearly_study_stats`
- `user_yearly_study_plan_stats`
- `user_yearly_test_stats`
- `user_yearly_test_publish_month_stats`
- `user_profile_stats`
- `library_material_report_reason_counters`
- `admin_yearly_financial_stats`
- `admin_yearly_financial_month_stats`
- `admin_yearly_test_sales_stats`
- `admin_yearly_test_activity_month_stats`
- `admin_yearly_library_material_activity_month_stats`

---

# 13) قواعد مهمة لبناء الـ API

## 13.1 Authentication / Access Control
- لا تمنح access token إلا بعد login ناجح
- المستخدم العادي لا يسجل الدخول إذا:
  - البريد غير مؤكد
  - أو الـ onboarding غير مكتمل
  - أو الحساب محظور
- admin/owner لا يحتاجان onboarding

## 13.2 Ownership Rules
- المستخدم لا يعدل إلا موارده
- في `test_folder` لا يضيف إلا اختبارات أنشأها هو
- في `study_plan` لا يربط إلا مواد أنشأها هو
- في `study_task` لا يربط إلا مادة موجودة داخل نفس الخطة

## 13.3 Visibility Rules
### Tests
- لا يظهر الاختبار العام للآخرين إلا إذا:
  - `review_status = approved`
- الاختبارات المدفوعة التي اشتراها المستخدم تبقى متاحة له وفق منطق النظام حتى لو توقفت عن الظهور للعامة
- الخاص لا يظهر للعامة

### Library Material
- لا يظهر المحتوى العام للآخرين إلا إذا:
  - `review_status = approved`
- الخاص لا يظهر للآخرين

### Test Folder
- ظهور المجلد للآخرين يعتمد على `visibility_type`
- المجلد العام لا يجوز أن يحتوي اختبارات خاصة

### Profile
- `phone` و `birth_date` لصاحب الحساب فقط
- `profile_slug` ثابت ويستخدم في الروابط

## 13.4 Study Plan Validation
- كل خطة:
  - حد أقصى 5 لكل مستخدم
  - خطة افتراضية واحدة فقط
- كل خطة:
  - أقصى 10 مواد
- كل مهمة:
  - ترتبط بمادة واحدة
  - تقع داخل حدود الخطة
- التداخل الزمني مسموح
- مجموع دقائق اليوم لا يجوز أن يتجاوز `daily_study_minutes`

## 13.5 Review Workflows
### Tests
- راجع:
  - `test_review_rounds`
  - `test_revision_requests`
  - `test_revision_change_logs`
  - `test_status_histories`

### Library Material
- راجع:
  - `library_material_review_rounds`
  - `library_material_status_histories`

## 13.6 Stats Update Strategy
عند بناء الـ API أو الـ services:
- اعتبر جداول summary جداول مشتقة
- حدّثها:
  - transactionally إن أمكن
  - أو عبر jobs/events إن كان الحمل كبيرًا
- لكنها يجب أن تبقى متناسقة مع الجداول الأصلية

---

# 14) ملاحظات هندسية أخيرة للأدوات الذكية

أي أداة ذكاء تستخدم هذا الملف لبناء الـ API يجب أن تراعي ما يلي:

1. **الجداول المعتمدة هنا هي المرجع الأساسي**
2. لا يجوز “اختراع” جداول إضافية إلا عند طلب صريح
3. بعض الجداول summary وليست source of truth
4. بعض الحقول/الأسماء محفوظة كما هي رغم وجود typos بسيطة
5. العلاقات بين الـ Modules مهمة جدًا، ولا سيما:
   - `users`
   - `interests`
   - `test`
   - `library_material`
   - `study_plan`
6. قبل بناء أي endpoint يجب تحديد:
   - هل هذا المورد public أم private؟
   - هل يحتاج ownership check؟
   - هل يحتاج review-status check؟
   - هل يؤثر على counters أو summary tables؟

---

# 16) الخلاصة

هذا الملف يصف:
- الجداول
- العلاقات
- قواعد العمل
- الجداول المشتقة للإحصائيات
- منطق الرؤية والصلاحيات
- نقاط التوسع
- وكيف يجب أن تفكر أي أداة ذكاء اصطناعي في هذه الداتابيز أثناء بناء الـ API

إذا تم استخدامه كمرجع داخل المشروع، فسيقلل جدًا الحاجة إلى إعادة شرح تصميم قاعدة البيانات في كل مرة.
