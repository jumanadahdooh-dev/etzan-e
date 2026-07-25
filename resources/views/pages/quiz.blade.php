@extends('layouts.public')

@section('title', 'تقييم الحاجة للمتابعة | اتزان')

@push('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('front/css/quiz.css') }}?v={{ filemtime(public_path('front/css/quiz.css')) }}">
@endpush

@section('content')
@php
    $quizImage = setting('home_quiz_image');
    $quizImageUrl = $quizImage ? asset('storage/' . $quizImage) : asset('front/image/quiz-doctor.png');
@endphp

<main class="quiz-page">
    <div class="quiz-bg-orb quiz-bg-orb-1"></div>
    <div class="quiz-bg-orb quiz-bg-orb-2"></div>
    <div class="quiz-bg-orb quiz-bg-orb-3"></div>

    <section class="quiz-shell">
        <aside class="quiz-side">
            <a href="{{ url('/') }}" class="quiz-back-link">
                <i class="fa-solid fa-arrow-right"></i>
                العودة للرئيسية
            </a>

            <span class="quiz-page-badge">
                <i class="fa-solid fa-shield-heart"></i>
                تقييم الحاجة للمتابعة
            </span>

            <h1>
                خلّينا نعرف
                <span>هل تحتاج متابعة؟</span>
                أو بداية بسيطة
            </h1>

            <p>
                هذا التقييم لا يقدم تشخيصًا طبيًا، بل يساعدك على معرفة هل الأفضل البدء بخطوات بسيطة، أو متابعة أخصائي تغذية، أو التواصل مع طبيب قبل أي خطة غذائية.
            </p>

            <div class="quiz-side-points">
                <div>
                    <i class="fa-solid fa-circle-check"></i>
                    <span>غير تشخيصي</span>
                </div>

                <div>
                    <i class="fa-solid fa-bolt"></i>
                    <span>نتيجة فورية</span>
                </div>

                <div>
                    <i class="fa-solid fa-user-doctor"></i>
                    <span>توجيه آمن</span>
                </div>
            </div>

            <div class="quiz-character-card">
                <div class="quiz-character-glow"></div>

                <div class="quiz-character-image">
                    <img src="{{ $quizImageUrl }}" alt="شخصية اتزان">
                </div>

                <div class="quiz-character-message">
                    <i class="fa-solid fa-comment-dots"></i>
                    <p id="quizCharacterText">ابدأ بالإجابة، وأنا رح أساعدك تعرف الاتجاه المناسب لك.</p>
                </div>
            </div>
        </aside>

        <section class="quiz-card">
            <div class="quiz-card-head">
                <div>
                    <span id="quizStepLabel">السؤال 1 من 5</span>
                    <h2 id="quizQuestionTitle">ما هدفك الحالي؟</h2>
                </div>

                <div class="quiz-progress-circle">
                    <strong id="quizProgressNumber">20%</strong>
                </div>
            </div>

            <div class="quiz-progress-line">
                <span id="quizProgressBar"></span>
            </div>

            <form id="quizForm">
                <div class="quiz-step is-active" data-step="0">
                    <div class="quiz-options">
                        <button type="button" class="quiz-option" data-key="goal" data-value="loss">
                            <i class="fa-solid fa-arrow-trend-down"></i>
                            <strong>خسارة وزن</strong>
                            <small>أريد تقليل الوزن بطريقة صحية</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="goal" data-value="gain">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                            <strong>زيادة وزن</strong>
                            <small>أريد زيادة صحية ومتوازنة</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="goal" data-value="balance">
                            <i class="fa-solid fa-scale-balanced"></i>
                            <strong>توازن غذائي</strong>
                            <small>أريد تحسين عاداتي اليومية</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="goal" data-value="health">
                            <i class="fa-solid fa-heart-pulse"></i>
                            <strong>تحسين الصحة</strong>
                            <small>أريد بداية صحية أوضح</small>
                        </button>
                    </div>
                </div>

                <div class="quiz-step" data-step="1">
                    <div class="quiz-options">
                        <button type="button" class="quiz-option" data-key="condition" data-value="diabetes">
                            <i class="fa-solid fa-droplet"></i>
                            <strong>سكري</strong>
                            <small>أحتاج انتباه أكبر للغذاء</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="condition" data-value="pressure">
                            <i class="fa-solid fa-stethoscope"></i>
                            <strong>ضغط</strong>
                            <small>أحتاج متابعة غذائية مناسبة</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="condition" data-value="allergy">
                            <i class="fa-solid fa-wheat-awn-circle-exclamation"></i>
                            <strong>حساسية طعام</strong>
                            <small>أحتاج اختيارات غذائية آمنة</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="condition" data-value="none">
                            <i class="fa-solid fa-circle-check"></i>
                            <strong>لا يوجد</strong>
                            <small>لا أعاني من حالة محددة</small>
                        </button>
                    </div>
                </div>

                <div class="quiz-step" data-step="2">
                    <div class="quiz-options">
                        <button type="button" class="quiz-option" data-key="activity" data-value="low">
                            <i class="fa-solid fa-couch"></i>
                            <strong>قليل</strong>
                            <small>حركتي اليومية محدودة</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="activity" data-value="medium">
                            <i class="fa-solid fa-person-walking"></i>
                            <strong>متوسط</strong>
                            <small>أتحرك بشكل مقبول يوميًا</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="activity" data-value="high">
                            <i class="fa-solid fa-person-running"></i>
                            <strong>عالي</strong>
                            <small>نشاطي أو تمريني مستمر</small>
                        </button>
                    </div>
                </div>

                <div class="quiz-step" data-step="3">
                    <div class="quiz-options">
                        <button type="button" class="quiz-option" data-key="symptoms" data-value="none">
                            <i class="fa-solid fa-circle-check"></i>
                            <strong>لا يوجد أعراض مقلقة</strong>
                            <small>لا أعاني من أعراض متكررة أو غير مفسرة</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="symptoms" data-value="dizziness">
                            <i class="fa-solid fa-face-dizzy"></i>
                            <strong>دوخة أو تعب شديد</strong>
                            <small>يتكرر معي بشكل ملحوظ</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="symptoms" data-value="weight_change">
                            <i class="fa-solid fa-weight-scale"></i>
                            <strong>تغير وزن غير مفسر</strong>
                            <small>زيادة أو نقصان بدون سبب واضح</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="symptoms" data-value="pain">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <strong>ألم أو مشكلة مستمرة</strong>
                            <small>أحتاج أتأكد قبل البدء</small>
                        </button>
                    </div>
                </div>

                <div class="quiz-step" data-step="4">
                    <div class="quiz-options">
                        <button type="button" class="quiz-option" data-key="medication" data-value="no">
                            <i class="fa-solid fa-circle-check"></i>
                            <strong>لا أستخدم أدوية باستمرار</strong>
                            <small>لا يوجد أدوية يومية أو مستمرة</small>
                        </button>

                        <button type="button" class="quiz-option" data-key="medication" data-value="yes">
                            <i class="fa-solid fa-pills"></i>
                            <strong>نعم، أستخدم أدوية</strong>
                            <small>لدي أدوية مستمرة أو موصوفة من طبيب</small>
                        </button>
                    </div>
                </div>

                <div class="quiz-result" id="quizResult">
                    <span class="quiz-result-badge">نتيجتك الأولية</span>

                    <div class="quiz-result-icon">
                        <i id="quizResultIcon" class="fa-solid fa-seedling"></i>
                    </div>

                    <h3 id="quizResultTitle">بداية غذائية متوازنة</h3>

                    <p id="quizResultText">
                        بناءً على إجاباتك، الأفضل أن تبدأ بخطوات بسيطة لتحسين العادات اليومية، مع متابعة محتوى مناسب داخل اتزان.
                    </p>

                    <div class="quiz-result-tags" id="quizResultTags">
                        <span>بداية واضحة</span>
                        <span>خطوات بسيطة</span>
                        <span>محتوى مناسب</span>
                    </div>

                    <div class="quiz-ai-boxes">
                        <div class="quiz-ai-box">
                            <h4>
                                <i class="fa-solid fa-lightbulb"></i>
                                توصيات مخصصة
                            </h4>
                            <div id="quizAiRecommendations"></div>
                        </div>

                        <div class="quiz-ai-box">
                            <h4>
                                <i class="fa-solid fa-newspaper"></i>
                                مقالات مناسبة لك
                            </h4>
                            <div id="quizAiArticles"></div>
                        </div>

                        <div class="quiz-ai-box">
                            <h4>
                                <i class="fa-solid fa-list-check"></i>
                                مهام يومية مقترحة
                            </h4>
                            <div id="quizAiTasks"></div>
                        </div>
                    </div>

                    <div class="quiz-result-actions">
                        <a href="{{ url('/doctors') }}" class="quiz-main-action">
                            تصفح الأطباء
                        </a>

                        <a href="{{ url('/articles') }}" class="quiz-secondary-action">
                            قراءة المقالات
                        </a>
                    </div>

                    <div class="quiz-disclaimer">
                        هذا التقييم لا يقدم تشخيصًا طبيًا ولا يغني عن استشارة الطبيب. هدفه فقط مساعدتك على معرفة الخطوة الأنسب كبداية.
                    </div>

                    <button type="button" class="quiz-restart-btn" id="quizRestartBtn">
                        إعادة التقييم
                    </button>
                </div>

                <div class="quiz-controls" id="quizControls">
                    <button type="button" class="quiz-prev-btn" id="quizPrevBtn" disabled>
                        <i class="fa-solid fa-arrow-right"></i>
                        السابق
                    </button>

                    <button type="button" class="quiz-next-btn" id="quizNextBtn" disabled>
                        التالي
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                </div>
            </form>
        </section>
    </section>
</main>
@endsection

@push('scripts')
<script>
    window.quizAnalyzeUrl = "{{ route('quiz.analyze') }}";
</script>

<script src="{{ asset('front/js/quiz.js') }}?v={{ filemtime(public_path('front/js/quiz.js')) }}"></script>
@endpush
