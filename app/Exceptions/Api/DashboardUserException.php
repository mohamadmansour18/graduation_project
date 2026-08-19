<?php

namespace App\Exceptions\Api;

class DashboardUserException extends ApiException
{
    public static function invalidUsersCursor(): self
    {
        return new self(
            title: '! مؤشر الصفحة غير صالح',
            message: 'يرجى إعادة تحميل قائمة المستخدمين بعد تغيير النوع أو الترتيب',
            status: 422
        );
    }

    public static function onlyOwnerCanCreateSupervisor(): self
    {
        return new self(
            title: '! غير مصرح',
            message: 'إضافة مشرف جديد متاحة للمالك فقط',
            status: 403
        );
    }

    public static function supervisorRoleNotFound(): self
    {
        return new self(
            title: '! فشل إنشاء المشرف',
            message: 'دور المشرف غير موجود في النظام',
            status: 500
        );
    }

    public static function academicVerificationAssetNotFound(): self
    {
        return new self(
            title: '! تعذر عرض الوثيقة',
            message: 'الوثيقة المطلوبة غير موجودة',
            status: 404
        );
    }

    public static function academicVerificationAssetFileNotFound(): self
    {
        return new self(
            title: '! تعذر عرض الوثيقة',
            message: 'ملف الوثيقة غير موجود في التخزين',
            status: 404
        );
    }

    public static function academicVerificationRequestNotFound(): self
    {
        return new self(
            title: '! طلب التوثيق غير موجود',
            message: 'طلب توثيق المستوى الأكاديمي المطلوب غير موجود',
            status: 404
        );
    }

    public static function academicVerificationRequestAlreadyApproved(): self
    {
        return new self(
            title: '! لا يمكن مراجعة طلب التوثيق',
            message: 'تمت الموافقة على طلب توثيق المستوى الأكاديمي مسبقاً',
            status: 409
        );
    }

    public static function academicVerificationRequestAlreadyRejected(): self
    {
        return new self(
            title: '! لا يمكن مراجعة طلب التوثيق',
            message: 'تم رفض طلب توثيق المستوى الأكاديمي مسبقاً',
            status: 409
        );
    }

    public static function userAlreadyBanned(): self
    {
        return new self(
            title: '! لا يمكن تنفيذ العملية',
            message: 'هذا المستخدم لديه حظر قائم أو مجدول مسبقاً',
            status: 409
        );
    }

    public static function banTargetUserNotFound(): self
    {
        return new self(
            title: '! خطأ تحقق',
            message: 'المستخدم المطلوب حظره غير موجود',
            status: 422
        );
    }

    public static function cannotBanOwner(): self
    {
        return new self(
            title: '! خطأ تحقق',
            message: 'لا يمكن حظر مالك التطبيق',
            status: 422
        );
    }

    public static function supervisorCanOnlyBanMobileUsers(): self
    {
        return new self(
            title: '! خطأ تحقق',
            message: 'المشرف يستطيع حظر مستخدمي الموبايل فقط، ولا يمكنه حظر مشرف آخر',
            status: 422
        );
    }

    public static function ownerCanOnlyBanMobileUsersAndSupervisors(): self
    {
        return new self(
            title: '! خطأ تحقق',
            message: 'مالك التطبيق يستطيع حظر مستخدمي الموبايل والمشرفين فقط',
            status: 422
        );
    }

    public static function roleCannotBanUsers(): self
    {
        return new self(
            title: '! غير مصرح',
            message: 'دور المستخدم الحالي لا يسمح له بحظر المستخدمين',
            status: 403
        );
    }

    public static function invalidTemporaryBanDuration(): self
    {
        return new self(
            title: '! مدة الحظر غير صالحة',
            message: 'مدة الحظر المؤقت يجب ألا تقل عن يوم واحد وألا تزيد عن 30 يوماً',
            status: 422
        );
    }

    public static function userNotFound(): self
    {
        return new self(
            title: '! غير موجود',
            message: 'المشرف او المالك المطلوب غير موجود',
            status: 404
        );
    }

    public static function supervisorNotFound(): self
    {
        return new self(
            title: '! غير موجود',
            message: 'المشرف او المالك المطلوب غير موجود',
            status: 404
        );
    }

    public static function invalidOldPassword(): self
    {
        return new self(
            title: '! فشل تعديل كلمة المرور',
            message: 'كلمة المرور القديمة غير صحيحة',
            status: 422
        );
    }

    public static function newPasswordMustBeDifferent(): self
    {
        return new self(
            title: '! فشل تعديل كلمة المرور',
            message: 'كلمة المرور الجديدة يجب أن تكون مختلفة عن كلمة المرور القديمة',
            status: 422
        );
    }
    public static function cannotDeleteLastScientificInterest(string $usageType): self
    {
        $message = match ($usageType) {
            'user' => 'لا يمكن حذف هذا التصنيف لأنه التصنيف العلمي الوحيد لدى مستخدم واحد على الأقل',
            'test' => 'لا يمكن حذف هذا التصنيف لأنه التصنيف العلمي الوحيد لاختبار واحد على الأقل',
            'library_material' => 'لا يمكن حذف هذا التصنيف لأنه التصنيف العلمي الوحيد لمحتوى علمي واحد على الأقل',
            default => 'لا يمكن حذف هذا التصنيف العلمي لأنه مستخدم كتصنيف وحيد داخل النظام',
        };

        return new self(
            title: '! لا يمكن حذف التصنيف العلمي',
            message: $message,
            status: 409
        );
    }

    public static function cannotDeleteCategoryWithOnlyOneInterest(): self
    {
        return new self(
            title: '! لا يمكن حذف عنوان التصنيف العلمي',
            message: 'لا يمكن حذف عنوان يحتوي على تصنيف علمي واحد فقط',
            status: 409
        );
    }

    public static function userHasNoActiveBan(): self
    {
        return new self(
            title: '! فشل رفع الحظر',
            message: 'لا يمكن رفع الحظر لأن المستخدم لا يملك حظراً فعالاً',
            status: 409
        );
    }

}
