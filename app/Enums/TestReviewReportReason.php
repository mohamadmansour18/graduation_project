<?php

namespace App\Enums;

enum TestReviewReportReason : string
{
    case Offensive_Comment = "تعليق مسيئ (أخلاقيا - دينيا - اجتماعيا)";

    case Harassment_Or_Personal_Abuse = "مضايقة او إساءة شخصية";

    case Irrelevant_Comment = "تعليق لا علاقة له بموضوع الاختبار (غير موضوعي)";
}
