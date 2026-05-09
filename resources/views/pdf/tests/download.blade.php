@php
    $normalizeText = static function (mixed $value): string {
        if ($value instanceof \BackedEnum) return (string) $value->value;
        if ($value instanceof \UnitEnum) return (string) $value->name;
        return trim((string) $value);
    };

    $containsArabic = static fn (mixed $text): bool => (bool) preg_match('/\p{Arabic}/u', $normalizeText($text));
    $containsLatin = static fn (mixed $text): bool => (bool) preg_match('/[A-Za-z]/u', $normalizeText($text));

    $resolveTextClass = static function (mixed $text) use ($containsArabic, $containsLatin, $normalizeText): string {
        $value = $normalizeText($text);

        if ($value === '') return 'rtl-text';

        $hasArabic = $containsArabic($value);
        $hasLatin = $containsLatin($value);

        return match (true) {
            $hasArabic && $hasLatin => 'mixed-text',
            $hasArabic => 'rtl-text',
            default => 'ltr-text',
        };
    };

    $duration = $test->duration_seconds
        ? ceil($test->duration_seconds / 60) . ' دقيقة'
        : 'غير محددة';

    $passMark = $test->pass_mark_percentage
        ? $test->pass_mark_percentage . '%'
        : 'غير محددة';

    $questions = $test->testQuestions?->sortBy('position')->values() ?? collect();
    $questionsCount = $questions->count();
@endphp

    <!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">

    <style>
        @page {
            margin: 16mm 12mm 20mm 12mm;
            footer: nerd-footer;
        }

        body {
            font-family: dejavusans, sans-serif;
            direction: rtl;
            text-align: right;
            color: #111827;
            font-size: 11pt;
            line-height: 1.8;
            background: #ffffff;
        }

        .rtl-text {
            direction: rtl;
            text-align: right;
            unicode-bidi: embed;
        }

        .ltr-text {
            direction: ltr;
            text-align: left;
            unicode-bidi: embed;
        }

        .mixed-text {
            direction: rtl;
            text-align: right;
            unicode-bidi: plaintext;
        }

        .cover {
            border: 1px solid #d9e2f1;
            border-radius: 14px;
            margin-bottom: 14px;
            background: #ffffff;
        }

        .header {
            color: #ffffff;
            padding: 20px 22px 22px 22px;
            border-radius: 14px 14px 0 0;

            background: #123c8c;
            background: linear-gradient(135deg, #0f2f6e 0%, #1d4ed8 55%, #60a5fa 100%);
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 86px;
            vertical-align: top;
            text-align: center;
        }

        .logo-img {
            width: 70px;
            height: auto;
            display: block;
        }

        .info-table,
        .question-table,
        .option-table,
        .answer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand {
            font-size: 9pt;
            color: #dbeafe;
            margin-bottom: 4px;
            direction: ltr;
            text-align: left;
        }

        .header-content-cell {
            vertical-align: top;
            padding-right: 18px;
            padding-left: 28px;
        }

        .title {
            font-size: 20pt;
            font-weight: bold;
            line-height: 1.55;
            margin: 0 0 14px 0;
            color: #ffffff;
        }

        .description {
            color: #eaf2ff;
            font-size: 10.5pt;
            line-height: 1.85;
            margin-top: 8px;
        }

        .stats-row {
            background: #f4f7fb;
            border-top: 1px solid #dbe3f0;
            border-radius: 0 0 14px 14px;
        }

        .stat {
            width: 33.33%;
            text-align: center;
            padding: 11px 6px;
            border-left: 1px solid #dbe3f0;
        }

        .stat:last-child {
            border-left: none;
        }

        .stat-value {
            display: block;
            font-size: 15pt;
            font-weight: bold;
            color: #0f2f6e;
            line-height: 1.3;
        }

        .stat-label {
            display: block;
            font-size: 9pt;
            color: #64748b;
        }

        .section {
            margin-bottom: 14px;
        }

        .section-heading {
            border-right: 5px solid #2563eb;
            padding: 4px 10px 5px;
            margin-bottom: 9px;
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
            background: #f3f7ff;
        }

        .info-card {
            border: 1px solid #dbe3f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 14px;
        }

        .info-table td {
            border: 1px solid #e5ecf6;
            padding: 8px 10px;
            vertical-align: top;
        }

        .info-label {
            width: 23%;
            background: #f1f5fb;
            color: #1e3a8a;
            font-weight: bold;
        }

        .info-value {
            width: 27%;
            background: #ffffff;
            color: #1f2937;
        }

        .question-card {
            page-break-inside: avoid;
            border: 1px solid #d7e2f0;
            border-radius: 10px;
            margin-bottom: 12px;
            background: #ffffff;
            overflow: hidden;
        }

        .question-head {
            background: #eff6ff;
            border-bottom: 1px solid #d7e2f0;
            padding: 8px 10px;
            color: #1e3a8a;
            font-weight: bold;
        }

        .question-content {
            padding: 10px 12px 12px;
        }

        .question-text {
            font-size: 12pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 9px;
            line-height: 1.9;
        }

        .hint-box {
            background: #fffbeb;
            border: 1px solid #f4cf76;
            border-radius: 8px;
            padding: 7px 9px;
            margin-bottom: 9px;
            color: #78350f;
        }

        .hint-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 2px;
        }

        .option-table {
            margin-bottom: 6px;
            border: 1px solid #e2e8f0;
            background: #fbfdff;
        }

        .option-table td {
            padding: 7px 9px;
            vertical-align: top;
        }

        .option-key-cell {
            width: 38px;
            text-align: center;
            background: #eef4ff;
            border-left: 1px solid #e2e8f0;
            color: #1d4ed8;
            font-weight: bold;
        }

        .option-text {
            color: #1f2937;
            line-height: 1.8;
        }

        .page-break {
            page-break-before: always;
        }

        .answer-card {
            border: 1px solid #d7e2f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .answer-table th {
            background: #0f2f6e;
            color: #ffffff;
            border: 1px solid #0f2f6e;
            padding: 9px;
            text-align: center;
            font-size: 10pt;
        }

        .answer-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 9px;
            vertical-align: top;
        }

        .answer-number,
        .answer-choice {
            text-align: center;
            font-weight: bold;
            color: #0f2f6e;
            background: #f8fafc;
        }

        .answer-number {
            width: 70px;
        }

        .answer-choice {
            width: 85px;
        }

        .empty-state {
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            color: #64748b;
            background: #f8fafc;
        }

        .footer-note {
            margin-top: 12px;
            text-align: center;
            color: #64748b;
            font-size: 9pt;
        }
    </style>
</head>

<body>

<htmlpagefooter name="nerd-footer">
    <div style="text-align: center; font-family: dejavusans, sans-serif; font-size: 9pt; color: #64748b;">
        page {PAGENO}
    </div>
</htmlpagefooter>

<sethtmlpagefooter name="nerd-footer" value="on" show-this-page="1" />

<div class="cover">
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <div class="logo-box">
                        @if(! empty($logoSrc))
                            <img class="logo-img" src="{{ $logoSrc }}" alt="Nerd Logo">
                        @endif
                    </div>
                </td>

                <td class="header-content-cell">
                    <div class="title {{ $resolveTextClass($test->title) }}">
                        {{ $test->title }}
                    </div>

                    <div class="description {{ $resolveTextClass($test->description) }}">
                        {{ $test->description ?: 'ملف اختبار منسق للطباعة والمراجعة، يحتوي على الأسئلة والخيارات وجدول الإجابات الصحيحة.' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="header-table stats-row">
        <tr>
            <td class="stat">
                <span class="stat-value">{{ $questionsCount }}</span>
                <span class="stat-label">عدد الأسئلة</span>
            </td>

            <td class="stat">
                <span class="stat-value">{{ $duration }}</span>
                <span class="stat-label">المدة</span>
            </td>

            <td class="stat">
                <span class="stat-value">{{ $passMark }}</span>
                <span class="stat-label">حد النجاح</span>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-heading">بيانات الاختبار</div>

    <div class="info-card">
        <table class="info-table">
            <tr>
                <td class="info-label">مستوى الصعوبة</td>
                <td class="info-value {{ $resolveTextClass($test->difficulty_level) }}">
                    {{ $normalizeText($test->difficulty_level) ?: 'غير محدد' }}
                </td>

                <td class="info-label">التصنيف الدراسي</td>
                <td class="info-value {{ $resolveTextClass($test->target_level) }}">
                    {{ $normalizeText($test->target_level) ?: 'غير محدد' }}
                </td>
            </tr>

            <tr>
                <td class="info-label">لغة الاختبار</td>
                <td class="info-value {{ $resolveTextClass($test->language) }}">
                    {{ $normalizeText($test->language) ?: 'غير محددة' }}
                </td>
                <td class="info-label">حد النجاح</td>
                <td class="info-value mixed-text">{{ $passMark }}</td>
            </tr>

            <tr>
                <td class="info-label">عدد الأسئلة</td>
                <td class="info-value mixed-text">{{ $questionsCount }}</td>


            </tr>
        </table>
    </div>

</div>

<div class="section">
    <div class="section-heading">الأسئلة</div>

    @forelse($questions as $question)
        <div class="question-card">
            <div class="question-head">
                السؤال {{ $question->position }}
            </div>

            <div class="question-content">
                <div class="question-text {{ $resolveTextClass($question->question_text) }}">
                    {{ $question->question_text }}
                </div>

                @if($question->hint_text)
                    <div class="hint-box">
                        <div class="hint-title">تلميح</div>
                        <div class="{{ $resolveTextClass($question->hint_text) }}">
                            {{ $question->hint_text }}
                        </div>
                    </div>
                @endif

                @foreach($question->testQuestionOptions?->sortBy('position') ?? collect() as $option)
                    <table class="option-table">
                        <tr>
                            <td class="option-key-cell">
                                {{ chr(64 + $option->position) }}
                            </td>

                            <td>
                                <div class="option-text {{ $resolveTextClass($option->option_text) }}">
                                    {{ $option->option_text }}
                                </div>
                            </td>
                        </tr>
                    </table>
                @endforeach
            </div>
        </div>
    @empty
        <div class="empty-state">
            لا توجد أسئلة مرتبطة بهذا الاختبار.
        </div>
    @endforelse
</div>

<div class="page-break"></div>

<div class="section">
    <div class="section-heading">جدول الإجابات الصحيحة</div>

    <div class="answer-card">
        <table class="answer-table">
            <thead>
            <tr>
                <th>رقم السؤال</th>
                <th>الخيار الصحيح</th>
                <th>نص الإجابة</th>
            </tr>
            </thead>

            <tbody>
            @foreach($questions as $question)
                @php
                    $correctOption = $question->testQuestionOptions?->firstWhere('is_correct', true);
                @endphp

                <tr>
                    <td class="answer-number">{{ $question->position }}</td>

                    <td class="answer-choice">
                        {{ $correctOption ? chr(64 + $correctOption->position) : '-' }}
                    </td>

                    <td>
                        <div class="{{ $resolveTextClass($correctOption?->option_text) }}">
                            {{ $correctOption?->option_text ?: 'غير محددة' }}
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer-note">
        ملف PDF صادر عن Nerd، مهيأ للطباعة والمراجعة والأرشفة.
    </div>
</div>

</body>
</html>
