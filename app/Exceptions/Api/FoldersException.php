<?php

namespace App\Exceptions\Api;

class FoldersException extends ApiException
{
    public static function folderTestsMustBeOwnedByUser(): self
    {
        return new self(
            title: '! لا يمكن إنشاء القائمة',
            message: 'جميع الاختبارات يجب أن تكون مملوكة لك',
            status: 422
        );
    }

    public static function folderTestsMustHaveSameType(): self
    {
        return new self(
            title: '! لا يمكن إنشاء القائمة',
            message: 'يجب أن تكون جميع الاختبارات إما عامة أو خاصة',
            status: 422
        );
    }

    public static function publicFolderTestsMustBeApproved(): self
    {
        return new self(
            title: '! لا يمكن إنشاء القائمة',
            message: 'الاختبارات العامة داخل القائمة يجب أن تكون موافق عليها',
            status: 422
        );
    }

    public static function folderNotFound(): self
    {
        return new self(
            title: '! القائمة غير موجودة',
            message: 'القائمة غير موجودة أو لا تملك صلاحية حذفها',
            status: 404
        );
    }

    public static function cannotChangePublicFolderToPrivate(): self
    {
        return new self(
            title: '! لا يمكن تعديل القائمة',
            message: 'لا يمكن تغيير القائمة العامة إلى خاصة',
            status: 422
        );
    }

    public static function publicFolderCannotContainPrivateTests(): self
    {
        return new self(
            title: '! لا يمكن تعديل القائمة',
            message: 'القائمة العامة لا يمكن أن تحتوي على اختبارات خاصة',
            status: 422
        );
    }
}
