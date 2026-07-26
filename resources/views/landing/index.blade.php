<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f9ff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#07101f" media="(prefers-color-scheme: dark)">
    <meta
        name="description"
        content="نيرد منصة تعليمية ذكية تجمع الاختبارات التفاعلية، المكتبة العلمية، الخطط الدراسية، والمجتمع المعرفي في تجربة واحدة."
    >
    <meta property="og:locale" content="ar_AR">
    <meta property="og:type" content="website">
    <meta property="og:title" content="نيرد | تعلّم بذكاء وتقدّم بثقة">
    <meta
        property="og:description"
        content="اختبارات تفاعلية، مكتبة علمية، خطط دراسة مخصصة، ومجتمع معرفي في مكان واحد."
    >
    <meta property="og:image" content="{{ asset('images/landing/nerd-logo.png') }}">

    <title>نيرد | تعلّم بذكاء وتقدّم بثقة</title>

    <link rel="icon" type="image/png" href="{{ asset('images/landing/nerd-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <script>
        (() => {
            try {
                const savedTheme = localStorage.getItem('nerd-theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const theme = savedTheme ?? (prefersDark ? 'dark' : 'light');

                document.documentElement.dataset.theme = theme;
                document.documentElement.style.colorScheme = theme;
            } catch {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>

    @vite(['resources/css/landing.css', 'resources/js/landing.js'])
</head>
<body>
    <a class="skip-link" href="#main-content">انتقل إلى المحتوى</a>
    <div class="scroll-progress" aria-hidden="true">
        <span id="scroll-progress-bar"></span>
    </div>

    <div class="page-atmosphere" aria-hidden="true">
        <span class="orb orb--one"></span>
        <span class="orb orb--two"></span>
        <span class="orb orb--three"></span>
        <span class="grid-glow"></span>
    </div>

    <header class="site-header" id="site-header">
        <div class="container header-inner">
            <a class="brand" href="#top" aria-label="نيرد، العودة إلى البداية">
                <img
                    src="{{ asset('images/landing/nerd-logo.png') }}"
                    width="48"
                    height="48"
                    alt=""
                >
                <span>
                    <strong>نيرد</strong>
                    <small>Nerd</small>
                </span>
            </a>

            <nav class="desktop-nav" aria-label="التنقل الرئيسي">
                <a href="#experience">التجربة</a>
                <a href="#features">المزايا</a>
                <a href="#audience">لمن نيرد؟</a>
                <a href="#contact">تواصل معنا</a>
            </nav>

            <div class="header-actions">
                <a class="header-explore" href="#experience">استكشف نيرد</a>
                <button
                    class="theme-toggle"
                    id="theme-toggle"
                    type="button"
                    aria-label="تفعيل الوضع الليلي"
                    aria-pressed="false"
                >
                    <span class="theme-toggle__track" aria-hidden="true">
                        <svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24">
                            <path d="M12 3v2m0 14v2M3 12h2m14 0h2M5.64 5.64l1.42 1.42m9.88 9.88 1.42 1.42m0-12.72-1.42 1.42M7.06 16.94l-1.42 1.42"/>
                            <circle cx="12" cy="12" r="3.75"/>
                        </svg>
                        <svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24">
                            <path d="M20 15.2A8.6 8.6 0 0 1 8.8 4a8.6 8.6 0 1 0 11.2 11.2Z"/>
                        </svg>
                        <span class="theme-toggle__thumb"></span>
                    </span>
                </button>
            </div>
        </div>
    </header>

    <main id="main-content">
        <section class="hero section" id="top">
            <div class="container hero-grid">
                <div class="hero-copy reveal">
                    <div class="eyebrow">
                        <span class="eyebrow-dot"></span>
                        منصة اختبارات ذكية
                    </div>

                    <h1>
                        المعرفة أقرب،
                        <span>والتعلّم أذكى.</span>
                    </h1>

                    <p class="hero-lead">
                        نيرد يجمع اختباراتك، موادك العلمية، خطتك الدراسية ومجتمعك
                        المعرفي في تجربة واحدة تساعدك على الفهم، لا الحفظ فقط.
                    </p>

                    <div class="hero-actions">
                        <a class="primary-button" href="#experience">
                            <span>شاهد نيرد عن قرب</span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </a>
                        <a class="text-link" href="#features">
                            اكتشف المزايا
                            <span aria-hidden="true">↓</span>
                        </a>
                    </div>

                    <div class="hero-facts" aria-label="أبرز خصائص نيرد">
                        <div>
                            <strong>03</strong>
                            <span>أنماط اختبار</span>
                        </div>
                        <span class="fact-divider" aria-hidden="true"></span>
                        <div>
                            <strong>01</strong>
                            <span>رحلة تعلّم متكاملة</span>
                        </div>
                        <span class="fact-divider" aria-hidden="true"></span>
                        <div>
                            <strong>∞</strong>
                            <span>مساحة للمعرفة</span>
                        </div>
                    </div>
                </div>

                <div class="hero-visual reveal reveal--delay" id="hero-visual">
                    <div class="visual-ring visual-ring--outer" aria-hidden="true"></div>
                    <div class="visual-ring visual-ring--inner" aria-hidden="true"></div>

                    <div class="floating-note floating-note--quiz" aria-hidden="true">
                        <span class="note-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="m9 11 2 2 4-5m4 4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                            </svg>
                        </span>
                        <span>
                            <small>تعلّم تفاعلي</small>
                            <strong>اختبر فهمك</strong>
                        </span>
                    </div>

                    <div class="phone-shell phone-shell--hero">
                        <div class="phone-speaker" aria-hidden="true"></div>
                        <div class="phone-screen">
                            <img
                                src="{{ asset('images/landing/home.png') }}"
                                width="373"
                                height="842"
                                alt="الشاشة الرئيسية لتطبيق نيرد"
                                fetchpriority="high"
                            >
                        </div>
                    </div>

                    <div class="floating-note floating-note--plan" aria-hidden="true">
                        <span class="plan-progress">
                            <svg viewBox="0 0 42 42">
                                <circle cx="21" cy="21" r="16"></circle>
                                <circle cx="21" cy="21" r="16"></circle>
                            </svg>
                            <strong>76%</strong>
                        </span>
                        <span>
                            <small>خطتك اليوم</small>
                            <strong>تقدّم مستمر</strong>
                        </span>
                    </div>

                    <div class="floating-pill floating-pill--library" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M5 4.5h10.5A2.5 2.5 0 0 1 18 7v12.5H7.5A2.5 2.5 0 0 1 5 17V4.5Zm0 12.5h13"/>
                        </svg>
                        مكتبة علمية
                    </div>
                </div>
            </div>

            <div class="container trust-strip reveal">
                <span>مصمم لرحلة تعلّم أكثر وضوحًا</span>
                <div>
                    <span>طلاب الجامعات</span>
                    <i></i>
                    <span>طلاب المدارس</span>
                    <i></i>
                    <span>المدرسون</span>
                    <i></i>
                    <span>المتعلمون ذاتيًا</span>
                </div>
            </div>
        </section>

        <section class="experience section" id="experience">
            <div class="container">
                <div class="section-heading section-heading--center reveal">
                    <span class="section-kicker">تجربة نيرد</span>
                    <h2>كل ما تحتاجه للدراسة،<br><span>في مكان واحد.</span></h2>
                    <p>
                        تنقّل بين أهم واجهات التطبيق واكتشف كيف تتحول رحلة الدراسة
                        من مصادر متفرقة إلى تجربة منظمة وتفاعلية.
                    </p>
                </div>

                <div class="experience-panel reveal">
                    <div class="experience-copy">
                        <div class="experience-counter" aria-hidden="true">
                            <span id="experience-current">01</span>
                            <i></i>
                            <span>07</span>
                        </div>

                        <div class="experience-tabs" role="tablist" aria-label="واجهات تطبيق نيرد">
                            <button
                                class="experience-tab is-active"
                                type="button"
                                role="tab"
                                aria-selected="true"
                                data-screen="{{ asset('images/landing/home.png') }}"
                                data-alt="الشاشة الرئيسية لتطبيق نيرد"
                                data-label="البداية"
                                data-title="واجهة تجمع يومك الدراسي"
                                data-description="اكتشف الاختبارات والمواد وأصحاب المعرفة من شاشة رئيسية واضحة ومخصصة لاهتماماتك."
                            >
                                <span class="tab-number">01</span>
                                <span>
                                    <strong>البداية</strong>
                                    <small>كل ما يهمك في لمحة واحدة</small>
                                </span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </button>

                            <button
                                class="experience-tab"
                                type="button"
                                role="tab"
                                aria-selected="false"
                                data-screen="{{ asset('images/landing/quiz-mcq.png') }}"
                                data-alt="اختبار اختيار من متعدد داخل تطبيق نيرد"
                                data-label="اختيار من متعدد"
                                data-title="اختبر فهمك لحظة بلحظة"
                                data-description="أسئلة واضحة، وقت محدد، ونتائج فورية تساعدك على معرفة نقاط القوة وما يحتاج إلى مراجعة."
                            >
                                <span class="tab-number">02</span>
                                <span>
                                    <strong>اختيار من متعدد</strong>
                                    <small>نتائج فورية وقياس واضح</small>
                                </span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </button>

                            <button
                                class="experience-tab"
                                type="button"
                                role="tab"
                                aria-selected="false"
                                data-screen="{{ asset('images/landing/quiz-flashcards.png') }}"
                                data-alt="بطاقات الاستذكار داخل تطبيق نيرد"
                                data-label="بطاقات الاستذكار"
                                data-title="راجع المعلومة بطريقة أخف"
                                data-description="حوّل الأسئلة إلى بطاقات سريعة، وصنّف ما تعرفه وما يحتاج إلى تكرار حتى تثبت المعلومة."
                            >
                                <span class="tab-number">03</span>
                                <span>
                                    <strong>بطاقات الاستذكار</strong>
                                    <small>مراجعة ذكية وسريعة</small>
                                </span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </button>

                            <button
                                class="experience-tab"
                                type="button"
                                role="tab"
                                aria-selected="false"
                                data-screen="{{ asset('images/landing/quiz-challenge.png') }}"
                                data-alt="نمط التحدي مع البوت داخل تطبيق نيرد"
                                data-label="تحدي البوت"
                                data-title="حوّل الاختبار إلى تحدٍ ممتع"
                                data-description="نافس بوت نيرد في جولة تفاعلية تضيف الحماس إلى المراجعة وتجعلك أكثر تركيزًا."
                            >
                                <span class="tab-number">04</span>
                                <span>
                                    <strong>تحدي البوت</strong>
                                    <small>المذاكرة بروح المنافسة</small>
                                </span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </button>

                            <button
                                class="experience-tab"
                                type="button"
                                role="tab"
                                aria-selected="false"
                                data-screen="{{ asset('images/landing/library.png') }}"
                                data-alt="المكتبة العلمية داخل تطبيق نيرد"
                                data-label="المكتبة العلمية"
                                data-title="مصادرك العلمية أقرب إليك"
                                data-description="تصفّح الصور والملفات والملاحظات، واحفظ المواد أو شاركها داخل مكتبة منظمة وسهلة البحث."
                            >
                                <span class="tab-number">05</span>
                                <span>
                                    <strong>المكتبة العلمية</strong>
                                    <small>ملفات وصور ومعرفة مشتركة</small>
                                </span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </button>

                            <button
                                class="experience-tab"
                                type="button"
                                role="tab"
                                aria-selected="false"
                                data-screen="{{ asset('images/landing/study-plan.png') }}"
                                data-alt="الخطة الدراسية داخل تطبيق نيرد"
                                data-label="الخطة الدراسية"
                                data-title="خطتك تتكيّف مع وقتك"
                                data-description="قسّم المواد إلى مهام قابلة للإنجاز، حدّد وقت الدراسة، وتابع تقدّمك يومًا بعد يوم."
                            >
                                <span class="tab-number">06</span>
                                <span>
                                    <strong>الخطة الدراسية</strong>
                                    <small>تنظيم ومتابعة وتقدّم</small>
                                </span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </button>

                            <button
                                class="experience-tab"
                                type="button"
                                role="tab"
                                aria-selected="false"
                                data-screen="{{ asset('images/landing/community.png') }}"
                                data-alt="ملف شخصي ضمن مجتمع نيرد المعرفي"
                                data-label="المجتمع المعرفي"
                                data-title="تعلّم مع أشخاص يلهمونك"
                                data-description="تابع أصحاب المعرفة، اكتشف محتواهم واختباراتهم، وكن جزءًا من مجتمع يشارك الفائدة."
                            >
                                <span class="tab-number">07</span>
                                <span>
                                    <strong>المجتمع المعرفي</strong>
                                    <small>تابع، اكتشف، وتبادل الفائدة</small>
                                </span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="experience-preview">
                        <div class="preview-backdrop" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>

                        <div class="preview-label" aria-live="polite">
                            <small id="experience-label">البداية</small>
                            <strong id="experience-title">واجهة تجمع يومك الدراسي</strong>
                            <p id="experience-description">
                                اكتشف الاختبارات والمواد وأصحاب المعرفة من شاشة رئيسية واضحة ومخصصة لاهتماماتك.
                            </p>
                        </div>

                        <div class="phone-shell phone-shell--preview" id="experience-phone">
                            <div class="phone-speaker" aria-hidden="true"></div>
                            <div class="phone-screen">
                                <img
                                    id="experience-image"
                                    src="{{ asset('images/landing/home.png') }}"
                                    width="373"
                                    height="842"
                                    alt="الشاشة الرئيسية لتطبيق نيرد"
                                >
                            </div>
                            <span class="swipe-hint" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
                                اسحب للتنقّل
                            </span>
                        </div>

                        <div class="preview-controls">
                            <button id="experience-next" type="button" aria-label="الواجهة التالية">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                            <div id="experience-dots" class="preview-dots" aria-hidden="true">
                                <span class="is-active"></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <button id="experience-previous" type="button" aria-label="الواجهة السابقة">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="features section" id="features">
            <div class="container">
                <div class="section-heading reveal">
                    <span class="section-kicker">لماذا نيرد؟</span>
                    <h2>أدوات صُممت حول<br><span>طريقة تعلّمك.</span></h2>
                    <p>
                        تجربة تربط الفهم بالتطبيق والتنظيم، لتقضي وقتًا أقل في
                        البحث ووقتًا أكثر في التقدّم.
                    </p>
                </div>

                <div class="feature-bento">
                    <article class="feature-card feature-card--quiz reveal">
                        <div class="card-topline">
                            <span class="feature-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="m9 11 2 2 4-5m4 4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                                </svg>
                            </span>
                            <span class="card-index">01</span>
                        </div>
                        <div>
                            <h3>اختبارات تتكيّف مع أسلوبك</h3>
                            <p>
                                اختر بين التحدي مع البوت، الاختيار من متعدد،
                                وبطاقات الاستذكار لتحوّل المعرفة إلى تدريب عملي.
                            </p>
                        </div>
                        <div class="quiz-modes" aria-hidden="true">
                            <span class="mode-card mode-card--front">
                                <small>MCQ</small>
                                <strong>اختر الإجابة</strong>
                                <i><b></b><b></b><b></b></i>
                            </span>
                            <span class="mode-card mode-card--middle">
                                <small>Flashcard</small>
                                <strong>اقلب البطاقة</strong>
                            </span>
                            <span class="mode-card mode-card--back">
                                <small>Challenge</small>
                                <strong>تحدَّ البوت</strong>
                            </span>
                        </div>
                    </article>

                    <article class="feature-card feature-card--library reveal reveal--delay">
                        <div class="card-topline">
                            <span class="feature-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M5 4.5h10.5A2.5 2.5 0 0 1 18 7v12.5H7.5A2.5 2.5 0 0 1 5 17V4.5Zm0 12.5h13"/>
                                </svg>
                            </span>
                            <span class="card-index">02</span>
                        </div>
                        <div>
                            <h3>مكتبة علمية تنمو بالمشاركة</h3>
                            <p>
                                صور وملفات وملاحظات منظمة حسب المادة، يمكنك
                                استكشافها أو حفظها خاصة بك.
                            </p>
                        </div>
                        <div class="library-stack" aria-hidden="true">
                            <span><i>PDF</i><b>ملخص علم الأحياء</b></span>
                            <span><i>IMG</i><b>خريطة الجهاز العصبي</b></span>
                            <span><i>NOTE</i><b>قواعد وملاحظات</b></span>
                        </div>
                    </article>

                    <article class="feature-card feature-card--plan reveal">
                        <div class="card-topline">
                            <span class="feature-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M7 3v3m10-3v3M4.5 9h15M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Zm2 8h3m2 0h3m-8 3h3"/>
                                </svg>
                            </span>
                            <span class="card-index">03</span>
                        </div>
                        <div>
                            <h3>خطة واضحة ليوم أكثر إنجازًا</h3>
                            <p>
                                أنشئ خطة شخصية، قسّمها إلى مهام، واضبط أوقات
                                الدراسة بما يناسب جدولك وتقدّمك.
                            </p>
                        </div>
                        <div class="plan-widget" aria-hidden="true">
                            <div>
                                <span>
                                    <small>تقدّم اليوم</small>
                                    <strong>3 من 4 مهام</strong>
                                </span>
                                <b>75%</b>
                            </div>
                            <i><span></span></i>
                            <ul>
                                <li class="is-done"><b></b> مراجعة الفصل الثالث</li>
                                <li class="is-done"><b></b> حل اختبار تجريبي</li>
                                <li><b></b> بطاقات الاستذكار</li>
                            </ul>
                        </div>
                    </article>

                    <article class="feature-card feature-card--community reveal reveal--delay">
                        <div class="card-topline">
                            <span class="feature-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M16 18a4 4 0 0 0-8 0m4-7a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7 7a3.5 3.5 0 0 0-3-3.46M17 5.2a2.7 2.7 0 0 1 0 5.2M5 18a3.5 3.5 0 0 1 3-3.46M7 5.2a2.7 2.7 0 0 0 0 5.2"/>
                                </svg>
                            </span>
                            <span class="card-index">04</span>
                        </div>
                        <div>
                            <h3>مجتمع يشارك المعرفة</h3>
                            <p>
                                تابع المدرسين وأصحاب المعرفة، واكتشف اختباراتهم
                                وموادهم الجديدة في مساحة تعليمية ملهمة.
                            </p>
                        </div>
                        <div class="community-orbit" aria-hidden="true">
                            <span class="avatar avatar--one">س</span>
                            <span class="avatar avatar--two">م</span>
                            <span class="avatar avatar--three">ع</span>
                            <span class="avatar avatar--four">ل</span>
                            <span class="community-center">
                                <img src="{{ asset('images/landing/nerd-logo.png') }}" alt="">
                            </span>
                            <svg viewBox="0 0 260 150">
                                <path d="M22 112C64 32 186 24 238 104"/>
                                <path d="M42 36c46 92 132 104 184 18"/>
                            </svg>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="audience section" id="audience">
            <div class="container">
                <div class="audience-intro reveal">
                    <span class="section-kicker">لمن صُمم نيرد؟</span>
                    <h2>مساحة واحدة،<br><span>لكل من يصنع المعرفة.</span></h2>
                </div>

                <div class="audience-grid">
                    <article class="audience-card reveal">
                        <span class="audience-number">01</span>
                        <div class="audience-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m3 9 9-5 9 5-9 5-9-5Zm4 3.2V17c3.2 2.2 6.8 2.2 10 0v-4.8"/>
                            </svg>
                        </div>
                        <h3>الطلاب</h3>
                        <p>
                            لطلاب الجامعات والمدارس الذين يريدون مراجعة تفاعلية،
                            مصادر مرتبة، وخطة تساعدهم على الاستمرار.
                        </p>
                        <span class="audience-tag">تعلّم • اختبر • تقدّم</span>
                    </article>

                    <article class="audience-card audience-card--featured reveal reveal--delay">
                        <span class="audience-number">02</span>
                        <div class="audience-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 19h16M6 16V7l6-3 6 3v9M9 10h6m-6 3h6"/>
                            </svg>
                        </div>
                        <h3>أصحاب المعرفة</h3>
                        <p>
                            للمدرسين والدكاترة وصنّاع المحتوى الذين يريدون تحويل
                            المواد العلمية إلى اختبارات منظمة وقابلة للمشاركة.
                        </p>
                        <span class="audience-tag">أنشئ • شارك • ألهم</span>
                    </article>

                    <article class="audience-card reveal">
                        <span class="audience-number">03</span>
                        <div class="audience-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3a7 7 0 0 0-4 12.75V19h8v-3.25A7 7 0 0 0 12 3Zm-3 16h6m-5 2h4"/>
                            </svg>
                        </div>
                        <h3>المتعلمون ذاتيًا</h3>
                        <p>
                            لكل من يتعلم من ملفات أو دورات ويبحث عن طريقة أذكى
                            لقياس فهمه وتنظيم ما يدرسه.
                        </p>
                        <span class="audience-tag">اكتشف • نظّم • أتقن</span>
                    </article>
                </div>
            </div>
        </section>

        <section class="closing section" id="contact">
            <div class="container">
                <div class="closing-card reveal">
                    <div class="closing-glow" aria-hidden="true"></div>
                    <div class="closing-mark" aria-hidden="true">
                        <img src="{{ asset('images/landing/nerd-logo.png') }}" alt="">
                    </div>
                    <div class="closing-copy">
                        <span class="section-kicker section-kicker--light">المعرفة تبدأ بخطوة</span>
                        <h2>مع نيرد، كل جلسة دراسة<br><span>تصبح تقدّمًا يمكن رؤيته.</span></h2>
                        <p>
                            منصة تعليمية عربية تجعل الاختبار والتنظيم والمشاركة
                            أجزاءً متصلة من رحلة واحدة.
                        </p>
                    </div>
                    <div class="contact-box">
                        <span>للتواصل والاستفسار</span>
                        <a href="mailto:nerd.app@gmail.com">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 6h18v12H3V6Zm1 1 8 6 8-6"/>
                            </svg>
                            nerd.app@gmail.com
                        </a>
                        <a href="https://instagram.com/nerd.app" target="_blank" rel="noreferrer">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="5"/>
                                <circle cx="12" cy="12" r="4"/>
                                <circle cx="17.5" cy="6.5" r="1"/>
                            </svg>
                            @nerd.app
                        </a>
                        <small>بيانات تجريبية مؤقتة</small>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <a class="brand brand--footer" href="#top" aria-label="نيرد، العودة إلى البداية">
                <img src="{{ asset('images/landing/nerd-logo.png') }}" width="42" height="42" alt="">
                <span>
                    <strong>نيرد</strong>
                    <small>تعلّم بذكاء</small>
                </span>
            </a>

            <p>© <span id="current-year">{{ date('Y') }}</span> نيرد. جميع الحقوق محفوظة.</p>

            <div class="footer-links">
                <a href="#experience">التجربة</a>
                <a href="#features">المزايا</a>
                <a href="#audience">لمن نيرد؟</a>
            </div>
        </div>
    </footer>
</body>
</html>
