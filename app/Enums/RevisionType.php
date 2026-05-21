<?php

namespace App\Enums;

enum RevisionType:string
{
    case QuestionText = 'نص السؤال';

    case AnswerText = 'نص الاجابة';

    case Hint = 'التلميح';

    case TestDescription = 'وصف الاختبار';

    case QuestionAnswer = 'إجابة السؤال';

    case TestTitle = 'عنوان الاختبار';
}
