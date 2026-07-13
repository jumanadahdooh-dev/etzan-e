document.addEventListener('DOMContentLoaded', function () {
    const questions = {
        goal: 'ما هدفك الحالي؟',
        condition: 'هل لديك حالة صحية تحتاج اهتمام؟',
        activity: 'ما مستوى نشاطك اليومي؟',
        symptoms: 'هل لديك أعراض متكررة أو غير مفسرة؟',
        medication: 'هل تستخدم أدوية بشكل مستمر؟'
    };

    const characterMessages = {
        goal: 'اختيار الهدف يساعدنا نحدد لك بداية أوضح.',
        condition: 'الحالة الصحية مهمة حتى تكون البداية مناسبة وآمنة.',
        activity: 'مستوى النشاط يساعدنا نفهم نمط يومك بشكل أفضل.',
        symptoms: 'الأعراض المتكررة مهمة حتى نوجهك للخطوة الأكثر أمانًا.',
        medication: 'استخدام الأدوية يحتاج انتباه قبل أي خطة غذائية.'
    };

    const form = document.getElementById('quizForm');
    const steps = Array.from(document.querySelectorAll('.quiz-step'));
    const options = Array.from(document.querySelectorAll('.quiz-option'));

    const prevBtn = document.getElementById('quizPrevBtn');
    const nextBtn = document.getElementById('quizNextBtn');
    const controls = document.getElementById('quizControls');

    const stepLabel = document.getElementById('quizStepLabel');
    const questionTitle = document.getElementById('quizQuestionTitle');
    const progressNumber = document.getElementById('quizProgressNumber');
    const progressBar = document.getElementById('quizProgressBar');
    const progressCircle = document.querySelector('.quiz-progress-circle');
    const characterText = document.getElementById('quizCharacterText');

    const result = document.getElementById('quizResult');
    const resultIcon = document.getElementById('quizResultIcon');
    const resultTitle = document.getElementById('quizResultTitle');
    const resultText = document.getElementById('quizResultText');
    const resultTags = document.getElementById('quizResultTags');
    const restartBtn = document.getElementById('quizRestartBtn');
    const resultActions = document.querySelector('.quiz-result-actions');

    const aiRecommendations = document.getElementById('quizAiRecommendations');
    const aiArticles = document.getElementById('quizAiArticles');
    const aiTasks = document.getElementById('quizAiTasks');

    let currentStep = 0;
    const answers = {};

    if (!form || steps.length === 0 || !nextBtn || !prevBtn || !result) {
        return;
    }

    function getCurrentStepKey() {
        const activeStep = steps[currentStep];
        const firstOption = activeStep ? activeStep.querySelector('.quiz-option') : null;

        return firstOption && firstOption.dataset.key
            ? firstOption.dataset.key
            : `step_${currentStep}`;
    }

    function hasAnswerForCurrentStep() {
        const key = getCurrentStepKey();
        return Boolean(answers[key]);
    }

    function updateUI() {
        steps.forEach((step, index) => {
            step.classList.toggle('is-active', index === currentStep);
        });

        const progress = ((currentStep + 1) / steps.length) * 100;
        const currentKey = getCurrentStepKey();

        if (stepLabel) {
            stepLabel.textContent = `السؤال ${currentStep + 1} من ${steps.length}`;
        }

        if (questionTitle) {
            questionTitle.textContent = questions[currentKey] || `السؤال ${currentStep + 1}`;
        }

        if (progressNumber) {
            progressNumber.textContent = `${Math.round(progress)}%`;
        }

        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }

        if (progressCircle) {
            progressCircle.style.background = `conic-gradient(var(--primary) ${progress}%, #dcefea 0)`;
        }

        if (characterText) {
            characterText.textContent = characterMessages[currentKey] || 'جاوب على السؤال لنحدد لك الخطوة الأنسب.';
        }

        prevBtn.disabled = currentStep === 0;
        nextBtn.disabled = !hasAnswerForCurrentStep();

        nextBtn.innerHTML = currentStep === steps.length - 1
            ? 'عرض النتيجة <i class="fa-solid fa-sparkles"></i>'
            : 'التالي <i class="fa-solid fa-arrow-left"></i>';
    }

    function selectOption(option) {
        const key = option.dataset.key;
        const value = option.dataset.value;
        const activeStep = option.closest('.quiz-step');

        if (!key || !value || !activeStep) {
            return;
        }

        answers[key] = value;

        activeStep.querySelectorAll('.quiz-option').forEach(item => {
            item.classList.remove('is-selected');
        });

        option.classList.add('is-selected');
        nextBtn.disabled = false;

        if (characterText) {
            characterText.textContent = 'تمام، سجلت إجابتك. خلينا نكمل خطوة بسيطة كمان.';
        }
    }

    async function analyzeQuiz() {
        nextBtn.disabled = true;
        nextBtn.innerHTML = 'جاري تحليل الإجابات...';

        try {
            if (!window.quizAnalyzeUrl) {
                throw new Error('quizAnalyzeUrl is missing');
            }

            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

            const response = await fetch(window.quizAnalyzeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    answers: answers
                })
            });

            if (!response.ok) {
                throw new Error('Quiz analyze request failed');
            }

            const data = await response.json();

            showResult(data);
        } catch (error) {
            console.error(error);

            showResult({
                type: 'error',
                icon: 'fa-solid fa-triangle-exclamation',
                title: 'تعذر تحليل الإجابات الآن',
                message: 'حدث خطأ أثناء تحليل النتيجة. تأكدي من وجود route باسم quiz.analyze وأن الكنترولر وقاعدة البيانات جاهزين.',
                character_message: 'في مشكلة بسيطة بالاتصال أو الإعدادات، خلينا نجرب بعد التأكد.',
                tags: ['تحقق من الإعدادات', 'Route مطلوب', 'Database مطلوب'],
                recommendations: [
                    'تأكدي من وجود route باسم quiz.analyze.',
                    'تأكدي من تشغيل migration الخاص بجدول quiz_recommendation_rules.',
                    'تأكدي أن QuizController يحتوي على دالة analyze.'
                ],
                articles: [],
                daily_tasks: [
                    'أعيدي التجربة بعد تعديل الإعدادات.'
                ],
                actions: [
                    {
                        label: 'إعادة التقييم',
                        url: '#',
                        style: 'primary'
                    }
                ]
            });
        }
    }

    function showResult(data) {
        steps.forEach(step => {
            step.classList.remove('is-active');
        });

        if (controls) {
            controls.style.display = 'none';
        }

        if (stepLabel) {
            stepLabel.textContent = 'اكتمل التقييم';
        }

        if (questionTitle) {
            questionTitle.textContent = 'نتيجتك الأولية';
        }

        if (progressNumber) {
            progressNumber.textContent = '100%';
        }

        if (progressBar) {
            progressBar.style.width = '100%';
        }

        if (progressCircle) {
            progressCircle.style.background = 'conic-gradient(var(--primary) 100%, #dcefea 0)';
        }

        if (characterText) {
            characterText.textContent = data.character_message || 'هذه نتيجة أولية تساعدك تبدأ، وليست تشخيصًا طبيًا.';
        }

        if (resultIcon) {
            resultIcon.className = data.icon || 'fa-solid fa-seedling';
        }

        if (resultTitle) {
            resultTitle.textContent = data.title || 'نتيجتك الأولية';
        }

        if (resultText) {
            resultText.textContent = data.message || 'هذه نتيجة مبدئية تساعدك على معرفة الخطوة الأنسب داخل اتزان.';
        }

        setTags(Array.isArray(data.tags) ? data.tags : []);
        updateActions(Array.isArray(data.actions) ? data.actions : []);

        renderAiList(aiRecommendations, data.recommendations || []);
        renderArticleList(aiArticles, data.articles || []);
        renderAiList(aiTasks, data.daily_tasks || []);

        result.classList.add('is-active');
    }

    function setTags(tags) {
        if (!resultTags) {
            return;
        }

        resultTags.innerHTML = '';

        if (tags.length === 0) {
            tags = ['تقييم أولي', 'بداية أوضح', 'غير تشخيصي'];
        }

        tags.forEach(tag => {
            const span = document.createElement('span');
            span.textContent = tag;
            resultTags.appendChild(span);
        });
    }

    function updateActions(actions) {
        if (!resultActions || actions.length === 0) {
            return;
        }

        resultActions.innerHTML = '';

        actions.forEach(action => {
            const link = document.createElement('a');

            link.href = action.url || '#';
            link.textContent = action.label || 'متابعة';

            if (action.url === '#') {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    resetQuiz();
                });
            }

            link.className = action.style === 'secondary'
                ? 'quiz-secondary-action'
                : 'quiz-main-action';

            resultActions.appendChild(link);
        });
    }

    function renderAiList(container, items) {
        if (!container) {
            return;
        }

        container.innerHTML = '';

        if (!Array.isArray(items) || items.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'quiz-empty-text';
            empty.textContent = 'لا توجد اقتراحات إضافية حاليًا.';
            container.appendChild(empty);
            return;
        }

        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'quiz-ai-item';

            const icon = document.createElement('i');
            icon.className = 'fa-solid fa-check';

            const span = document.createElement('span');
            span.textContent = item;

            div.appendChild(icon);
            div.appendChild(span);

            container.appendChild(div);
        });
    }

    function renderArticleList(container, articles) {
        if (!container) {
            return;
        }

        container.innerHTML = '';

        if (!Array.isArray(articles) || articles.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'quiz-empty-text';
            empty.textContent = 'لا توجد مقالات مناسبة حاليًا.';
            container.appendChild(empty);
            return;
        }

        articles.forEach(article => {
            const link = document.createElement('a');
            link.className = 'quiz-ai-item quiz-ai-article';

            const isObject = typeof article === 'object' && article !== null;

            link.href = isObject && article.url ? article.url : '/articles';

            const icon = document.createElement('i');
            icon.className = 'fa-solid fa-newspaper';

            const content = document.createElement('span');
            content.textContent = isObject
                ? (article.title || 'مقال توعوي')
                : article;

            link.appendChild(icon);
            link.appendChild(content);

            container.appendChild(link);
        });
    }

    function resetQuiz() {
        currentStep = 0;

        Object.keys(answers).forEach(key => {
            delete answers[key];
        });

        options.forEach(option => {
            option.classList.remove('is-selected');
        });

        result.classList.remove('is-active');

        if (controls) {
            controls.style.display = 'flex';
        }

        if (characterText) {
            characterText.textContent = 'ابدأ بالإجابة، وأنا رح أساعدك تعرف الاتجاه المناسب لك.';
        }

        updateUI();
    }

    options.forEach(option => {
        option.addEventListener('click', function () {
            selectOption(this);
        });
    });

    nextBtn.addEventListener('click', function () {
        if (!hasAnswerForCurrentStep()) {
            return;
        }

        if (currentStep < steps.length - 1) {
            currentStep++;
            updateUI();
        } else {
            analyzeQuiz();
        }
    });

    prevBtn.addEventListener('click', function () {
        if (currentStep > 0) {
            currentStep--;
            updateUI();
        }
    });

    if (restartBtn) {
        restartBtn.addEventListener('click', resetQuiz);
    }

    updateUI();
});
