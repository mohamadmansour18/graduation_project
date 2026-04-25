<?php

namespace App\Enums;

enum TargetLevel:string
{
    case GENERAL_INFO = 'معلومات عامة';

    case MASTER = 'ماجستير';
    case DOCTORATE = 'دكتوراه';

    case UNIVERSITY_YEAR_1 = 'سنة اولى جامعة';
    case UNIVERSITY_YEAR_2 = 'سنة ثانية جامعة';
    case UNIVERSITY_YEAR_3 = 'سنة ثالثة جامعة';
    case UNIVERSITY_YEAR_4 = 'سنة رابعة جامعة';
    case UNIVERSITY_YEAR_5 = 'سنة خامسة جامعة';
    case UNIVERSITY_YEAR_6 = 'سنة سادسة جامعة';

    case INSTITUTE_YEAR_1 = 'سنة اولى معهد';
    case INSTITUTE_YEAR_2 = 'سنة ثانية معهد';
    case INSTITUTE_YEAR_3 = 'سنة ثالثة معهد';

    case SCHOOL_GRADE_1 = 'الصف الأول';
    case SCHOOL_GRADE_2 = 'الصف الثاني';
    case SCHOOL_GRADE_3 = 'الصف الثالث';
    case SCHOOL_GRADE_4 = 'الصف الرابع';
    case SCHOOL_GRADE_5 = 'الصف الخامس';
    case SCHOOL_GRADE_6 = 'الصف السادس';
    case SCHOOL_GRADE_7 = 'الصف السابع';
    case SCHOOL_GRADE_8 = 'الصف الثامن';
    case SCHOOL_GRADE_9 = 'الصف التاسع';
    case SCHOOL_GRADE_10 = 'الصف العاشر';
    case SCHOOL_GRADE_11 = 'الصف الحادي عشر';
    case BACCALAUREATE = 'البكلوريا';
}
