<?php

namespace App\Enums;

enum TestReportsReason:string
{
    case Offensive_Content = "اختبار محتواه مسيء (أخلاقيا - دينيا - اجتماعيا)";

    case Question_Wording_Error = "يوجد أخطاء في طريقة صياغة السؤال";

    case Question_Choices_Error = "يوجد خطأ في الاختيارات المطروحة في السؤال";

    case Incorrect_Correct_Answer = "الإجابة الصحيحة لا يجب ان تكون هي الإجابة";

    case Explanation_Error = "يوجد خطأ في الشرح";
}
